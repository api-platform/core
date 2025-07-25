<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\JsonLd;

use ApiPlatform\JsonLd\Serializer\HydraPrefixTrait;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextBuilder implements AnonymousContextBuilderInterface
{
    use ClassInfoTrait;
    use HydraPrefixTrait;

    public const FORMAT = 'jsonld';
    public const HYDRA_PREFIX = 'hydra:';
    public const HYDRA_CONTEXT_HAS_PREFIX = 'hydra_prefix';

    public function __construct(private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly UrlGeneratorInterface $urlGenerator, private readonly ?IriConverterInterface $iriConverter = null, private readonly ?NameConverterInterface $nameConverter = null, private array $defaultContext = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseContext(int $referenceType = UrlGeneratorInterface::ABS_URL): array
    {
        return [
            '@vocab' => $this->urlGenerator->generate('api_doc', ['_format' => self::FORMAT], UrlGeneratorInterface::ABS_URL).'#',
            'hydra' => self::HYDRA_NS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntrypointContext(int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        $context = $this->getBaseContext($referenceType);

        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $shortName = $this->resourceMetadataFactory->create($resourceClass)[0]->getShortName();
            $resourceName = lcfirst($shortName);

            $context[$resourceName] = [
                '@id' => 'Entrypoint/'.$resourceName,
                '@type' => '@id',
            ];
        }

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContext(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        /** @var HttpOperation $operation */
        $operation = $this->resourceMetadataFactory->create($resourceClass)->getOperation(null, false, true);
        if (null === $shortName = $operation->getShortName()) {
            return [];
        }

        $context = $operation->getNormalizationContext();
        if ($context['iri_only'] ?? false) {
            $context = $this->getBaseContext($referenceType);
            $context[$this->getHydraPrefix($context).'member']['@type'] = '@id';

            return $context;
        }

        return $this->getResourceContextWithShortname($resourceClass, $referenceType, $shortName, $operation);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContextUri(string $resourceClass, ?int $referenceType = null): string
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass)[0];
        if (null === $referenceType) {
            $referenceType = $resourceMetadata->getUrlGenerationStrategy();
        }

        return $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $resourceMetadata->getShortName()], $referenceType ?? UrlGeneratorInterface::ABS_PATH);
    }

    /**
     * {@inheritdoc}
     */
    public function getAnonymousResourceContext(object $object, array $context = [], int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        $outputClass = $this->getObjectClass($object);
        $operation = $context['operation'] ?? new Get(
            shortName: (new \ReflectionClass($outputClass))->getShortName(),
            normalizationContext: [
                self::HYDRA_CONTEXT_HAS_PREFIX => $context[self::HYDRA_CONTEXT_HAS_PREFIX] ?? $this->defaultContext[self::HYDRA_CONTEXT_HAS_PREFIX] ?? true,
                'groups' => [],
            ],
            denormalizationContext: [
                'groups' => [],
            ]
        );
        $shortName = $operation->getShortName();

        $jsonLdContext = [
            '@context' => $this->getResourceContextWithShortname(
                $outputClass,
                $referenceType,
                $shortName,
                $operation
            ),
            '@type' => $shortName,
        ];

        if (isset($context['iri'])) {
            $jsonLdContext['@id'] = $context['iri'];
        } elseif (true === ($context['gen_id'] ?? true) && $this->iriConverter) {
            $jsonLdContext['@id'] = $this->iriConverter->getIriFromResource($object);
        }

        if ($context['has_context'] ?? false) {
            unset($jsonLdContext['@context']);
        }

        // here the object can be different from the resource given by the $context['api_resource'] value
        // TODO: this is probably not used anymore and is slow we get that @type way earlier, remove this
        if (isset($context['api_resource'])) {
            $jsonLdContext['@type'] = $this->resourceMetadataFactory->create($this->getObjectClass($context['api_resource']))[0]->getShortName();
        }

        return $jsonLdContext;
    }

    private function getResourceContextWithShortname(string $resourceClass, int $referenceType, string $shortName, ?HttpOperation $operation = null): array
    {
        $context = $this->getBaseContext($referenceType);
        $propertyContext = $operation ? ['normalization_groups' => $operation->getNormalizationContext()['groups'] ?? null, 'denormalization_groups' => $operation->getDenormalizationContext()['groups'] ?? null] : ['normalization_groups' => [], 'denormalization_groups' => []];

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName, $propertyContext);

            if ($propertyMetadata->isIdentifier() && true !== $propertyMetadata->isWritable()) {
                continue;
            }

            $convertedName = $this->nameConverter ? $this->nameConverter->normalize($propertyName, $resourceClass, self::FORMAT) : $propertyName;
            $jsonldContext = $propertyMetadata->getJsonldContext() ?? [];

            if ($id = $propertyMetadata->getIris()) {
                $id = 1 === (is_countable($id) ? \count($id) : 0) ? $id[0] : $id;
            }

            if (!$id) {
                $id = \sprintf('%s/%s', $shortName, $convertedName);
            }

            if (false === $propertyMetadata->isReadableLink()) {
                $jsonldContext += [
                    '@id' => $id,
                    '@type' => '@id',
                ];
            }

            if (empty($jsonldContext)) {
                $context[$convertedName] = $id;
            } else {
                $context[$convertedName] = $jsonldContext + [
                    '@id' => $id,
                ];
            }
        }

        return $context;
    }
}
