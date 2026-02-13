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

namespace ApiPlatform\JsonLd\Serializer;

use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\TagCollectorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts between objects and array including JSON-LD and Hydra metadata (normalization only).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use ItemNormalizerTrait;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, private readonly ContextBuilderInterface $contextBuilder, ?PropertyAccessorInterface $propertyAccessor = null, ?NameConverterInterface $nameConverter = null, ?ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], ?ResourceAccessCheckerInterface $resourceAccessChecker = null, protected ?TagCollectorInterface $tagCollector = null, private ?OperationMetadataFactoryInterface $operationMetadataFactory = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $defaultContext, $resourceMetadataCollectionFactory, $resourceAccessChecker, $tagCollector);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? parent::getSupportedTypes($format) : [];
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated since API Platform 4.2, use {@see ItemDenormalizer} instead
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        trigger_deprecation(
            'api-platform/core',
            '4.2',
            'Using "%s" for denormalization is deprecated, use "%s" instead.',
            self::class,
            ItemDenormalizer::class
        );

        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @throws LogicException
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $resourceClass = $this->getObjectClass($data);
        $outputClass = $this->getOutputClass($context);

        if ($outputClass && !($context['item_uri_template'] ?? null)) {
            return parent::normalize($data, $format, $context);
        }

        // TODO: we should not remove the resource_class in the normalizeRawCollection as we would find out anyway that it's not the same as the requested one
        $previousResourceClass = $context['resource_class'] ?? null;
        $metadata = [];
        if ($isResourceClass = $this->resourceClassResolver->isResourceClass($resourceClass) && (null === $previousResourceClass || $this->resourceClassResolver->isResourceClass($previousResourceClass))) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($data, $previousResourceClass);
            $context = $this->initContext($resourceClass, $context);
            $metadata = $this->addJsonLdContext($this->contextBuilder, $resourceClass, $context);
        } elseif ($this->contextBuilder instanceof AnonymousContextBuilderInterface) {
            if ($context['api_collection_sub_level'] ?? false) {
                unset($context['api_collection_sub_level']);
                $context['output']['gen_id'] ??= true;
                $context['output']['iri'] = null;
            }

            if (isset($context['item_uri_template']) && $this->operationMetadataFactory) {
                $context['output']['operation'] = $this->operationMetadataFactory->create($context['item_uri_template']);
            } elseif ($this->resourceClassResolver->isResourceClass($resourceClass)) {
                $context['output']['operation'] = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation();
            }

            // We should improve what's behind the context creation, its probably more complicated then it should
            $metadata = $this->createJsonLdContext($this->contextBuilder, $data, $context);
        }

        // Special case: non-resource got serialized and contains a resource therefore we need to reset part of the context
        if ($previousResourceClass !== $resourceClass && $resourceClass !== $outputClass) {
            unset($context['operation'], $context['operation_name'], $context['output']);
        }

        if (true === ($context['output']['gen_id'] ?? true) && true === ($context['force_iri_generation'] ?? true) && $iri = $this->iriConverter->getIriFromResource($data, UrlGeneratorInterface::ABS_PATH, $context['operation'] ?? null, $context)) {
            $context['iri'] = $iri;
            $metadata['@id'] = $iri;
        }

        $context['api_normalize'] = true;

        $normalizedData = parent::normalize($data, $format, $context);
        if (!\is_array($normalizedData)) {
            return $normalizedData;
        }

        $operation = $context['operation'] ?? null;

        if ($this->operationMetadataFactory && isset($context['item_uri_template']) && !$operation) {
            $operation = $this->operationMetadataFactory->create($context['item_uri_template']);
        }

        if ($isResourceClass && !$operation) {
            $operation = $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation();
        }

        if (!isset($metadata['@type']) && $operation) {
            $types = $operation instanceof HttpOperation ? $operation->getTypes() : null;
            if (null === $types) {
                $types = [$operation->getShortName()];
            }
            $metadata['@type'] = 1 === \count($types) ? $types[0] : $types;
        }

        return $metadata + $normalizedData;
    }
}
