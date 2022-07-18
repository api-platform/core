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

use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Util\ClassInfoTrait;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextBuilder implements AnonymousContextBuilderInterface
{
    use ClassInfoTrait;

    public const FORMAT = 'jsonld';

    private $resourceNameCollectionFactory;
    private ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory;
    private PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory;
    private PropertyMetadataFactoryInterface $propertyMetadataFactory;
    private UrlGeneratorInterface $urlGenerator;
    private ?NameConverterInterface $nameConverter = null;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, $resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, UrlGeneratorInterface $urlGenerator, NameConverterInterface $nameConverter = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->urlGenerator = $urlGenerator;
        $this->nameConverter = $nameConverter;
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
        /** @var HttpOperation */
        $operation = $this->resourceMetadataFactory->create($resourceClass)->getOperation(null, false, true);
        if (null === $shortName = $operation->getShortName()) {
            return [];
        }

        if ($operation->getNormalizationContext()['iri_only'] ?? false) {
            $context = $this->getBaseContext($referenceType);
            $context['hydra:member']['@type'] = '@id';

            return $context;
        }

        return $this->getResourceContextWithShortname($resourceClass, $referenceType, $shortName, $operation);
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceContextUri(string $resourceClass, int $referenceType = null): string
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass)[0];
        if (null === $referenceType) {
            $referenceType = $resourceMetadata->getUrlGenerationStrategy();
        }

        return $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $resourceMetadata->getShortName()], $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function getAnonymousResourceContext($object, array $context = [], int $referenceType = UrlGeneratorInterface::ABS_PATH): array
    {
        $outputClass = $this->getObjectClass($object);
        $operation = $context['operation'] ?? new Get(shortName: (new \ReflectionClass($outputClass))->getShortName());
        $shortName = $operation->getShortName();

        $jsonLdContext = [
            '@context' => $this->getResourceContextWithShortname(
                $outputClass,
                $referenceType,
                $shortName
            ),
            '@type' => $shortName,
        ];

        if (!isset($context['iri']) || false !== $context['iri']) {
            $jsonLdContext['@id'] = $context['iri'] ?? '/.well-known/genid/'.bin2hex(random_bytes(10));
        }

        if ($context['has_context'] ?? false) {
            unset($jsonLdContext['@context']);
        }

        // here the object can be different from the resource given by the $context['api_resource'] value
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
                $id = 1 === \count($id) ? $id[0] : $id;
            }

            if (!$id) {
                $id = sprintf('%s/%s', $shortName, $convertedName);
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
