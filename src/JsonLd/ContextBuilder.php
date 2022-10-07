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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface as LegacyPropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface as LegacyPropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Util\ClassInfoTrait;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * {@inheritdoc}
 * TODO: 3.0 simplify or remove the class.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ContextBuilder implements AnonymousContextBuilderInterface
{
    use ClassInfoTrait;

    public const FORMAT = 'jsonld';

    private $resourceNameCollectionFactory;
    /**
     * @param ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory
     */
    private $resourceMetadataFactory;
    /**
     * @var LegacyPropertyNameCollectionFactoryInterface|PropertyNameCollectionFactoryInterface
     */
    private $propertyNameCollectionFactory;
    /**
     * @var LegacyPropertyMetadataFactoryInterface|PropertyMetadataFactoryInterface
     */
    private $propertyMetadataFactory;
    private $urlGenerator;

    /**
     * @var NameConverterInterface|null
     */
    private $nameConverter;

    private $iriConverter;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, $resourceMetadataFactory, $propertyNameCollectionFactory, $propertyMetadataFactory, UrlGeneratorInterface $urlGenerator, NameConverterInterface $nameConverter = null, IriConverterInterface $iriConverter = null)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->urlGenerator = $urlGenerator;
        $this->nameConverter = $nameConverter;
        $this->iriConverter = $iriConverter;

        if ($resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }
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
            // TODO: remove in 3.0
            if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                $shortName = $this->resourceMetadataFactory->create($resourceClass)->getShortName();
            } else {
                $shortName = $this->resourceMetadataFactory->create($resourceClass)[0]->getShortName();
            }

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
        // TODO: Remove in 3.0
        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            if (null === $shortName = $resourceMetadata->getShortName()) {
                return [];
            }

            if ($resourceMetadata->getAttribute('normalization_context')['iri_only'] ?? false) {
                $context = $this->getBaseContext($referenceType);
                $context['hydra:member']['@type'] = '@id';

                return $context;
            }

            return $this->getResourceContextWithShortname($resourceClass, $referenceType, $shortName);
        }

        $operation = $this->resourceMetadataFactory->create($resourceClass)->getOperation();
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
        // TODO: remove in 3.0
        if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            if (null === $referenceType) {
                $referenceType = $resourceMetadata->getAttribute('url_generation_strategy');
            }

            return $this->urlGenerator->generate('api_jsonld_context', ['shortName' => $resourceMetadata->getShortName()], $referenceType);
        }

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
        $shortName = (new \ReflectionClass($outputClass))->getShortName();

        $jsonLdContext = [
            '@context' => $this->getResourceContextWithShortname(
                $outputClass,
                $referenceType,
                $shortName
            ),
            '@type' => $shortName,
        ];

        if (!isset($context['iri']) || false !== $context['iri']) {
            // Not using an IriConverter here is deprecated in 2.7, avoid spl_object_hash as it may collide
            if (isset($this->iriConverter)) {
                $jsonLdContext['@id'] = $context['iri'] ?? $this->iriConverter->getIriFromResource($object);
            } else {
                $jsonLdContext['@id'] = $context['iri'] ?? '/.well-known/genid/'.bin2hex(random_bytes(10));
            }
        }

        if ($this->iriConverter && isset($context['gen_id']) && true === $context['gen_id']) {
            $jsonLdContext['@id'] = $this->iriConverter->getIriFromResource($object);
        }

        if (false === ($context['iri'] ?? null)) {
            trigger_deprecation('api-platform/core', '2.7', 'An anonymous resource will use a Skolem IRI in API Platform 3.0. Use #[ApiProperty(genId: false)] to keep this behavior in 3.0.');
        }

        if ($context['has_context'] ?? false) {
            unset($jsonLdContext['@context']);
        }

        // here the object can be different from the resource given by the $context['api_resource'] value
        if (isset($context['api_resource'])) {
            if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                $jsonLdContext['@type'] = $this->resourceMetadataFactory->create($this->getObjectClass($context['api_resource']))->getShortName();
            } else {
                $jsonLdContext['@type'] = $this->resourceMetadataFactory->create($this->getObjectClass($context['api_resource']))[0]->getShortName();
            }
        }

        return $jsonLdContext;
    }

    private function getResourceContextWithShortname(string $resourceClass, int $referenceType, string $shortName, ?HttpOperation $operation = null): array
    {
        $context = $this->getBaseContext($referenceType);
        if ($this->propertyMetadataFactory instanceof LegacyPropertyMetadataFactoryInterface) {
            $propertyContext = [];
        } else {
            $propertyContext = $operation ? ['normalization_groups' => $operation->getNormalizationContext()['groups'] ?? null, 'denormalization_groups' => $operation->getDenormalizationContext()['groups'] ?? null] : ['normalization_groups' => [], 'denormalization_groups' => []];
        }

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            /** @var PropertyMetadata|ApiProperty */
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName, $propertyContext);

            if ($propertyMetadata->isIdentifier() && true !== $propertyMetadata->isWritable()) {
                continue;
            }

            $convertedName = $this->nameConverter ? $this->nameConverter->normalize($propertyName, $resourceClass, self::FORMAT) : $propertyName;
            if ($propertyMetadata instanceof PropertyMetadata) {
                $jsonldContext = ($propertyMetadata->getAttributes() ?? [])['jsonld_context'] ?? [];
                $id = $propertyMetadata->getIri();
            } else {
                $jsonldContext = $propertyMetadata->getJsonldContext() ?? [];

                if ($id = $propertyMetadata->getIris()) {
                    $id = 1 === \count($id) ? $id[0] : $id;
                }
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

class_alias(ContextBuilder::class, \ApiPlatform\Core\JsonLd\ContextBuilder::class);
