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

namespace ApiPlatform\JsonApi\Serializer;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\TagCollectorInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts between objects and array (normalization only).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ItemNormalizer extends AbstractItemNormalizer
{
    use ItemNormalizerTrait;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ?PropertyAccessorInterface $propertyAccessor = null, ?NameConverterInterface $nameConverter = null, ?ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, ?ResourceAccessCheckerInterface $resourceAccessChecker = null, protected ?TagCollectorInterface $tagCollector = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $defaultContext, $resourceMetadataCollectionFactory, $resourceAccessChecker, $tagCollector);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format, $context) && !($data instanceof \Exception || $data instanceof FlattenException);
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
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $resourceClass = $this->getObjectClass($data);
        if ($this->getOutputClass($context)) {
            return parent::normalize($data, $format, $context);
        }

        $previousResourceClass = $context['resource_class'] ?? null;
        if ($this->resourceClassResolver->isResourceClass($resourceClass) && (null === $previousResourceClass || $this->resourceClassResolver->isResourceClass($previousResourceClass))) {
            $resourceClass = $this->resourceClassResolver->getResourceClass($data, $previousResourceClass);
        }

        if (($operation = $context['operation'] ?? null) && method_exists($operation, 'getItemUriTemplate')) {
            $context['item_uri_template'] = $operation->getItemUriTemplate();
        }

        $context = $this->initContext($resourceClass, $context);

        $iri = $context['iri'] ??= $this->iriConverter->getIriFromResource($data, UrlGeneratorInterface::ABS_PATH, $context['operation'] ?? null, $context);
        $context['object'] = $data;
        $context['format'] = $format;
        $context['api_normalize'] = true;

        if (!isset($context['cache_key'])) {
            $context['cache_key'] = $this->getCacheKey($format, $context);
        }

        $normalizedData = parent::normalize($data, $format, $context);
        if (!\is_array($normalizedData)) {
            return $normalizedData;
        }

        // Get and populate relations
        ['relationships' => $allRelationshipsData, 'links' => $links] = $this->getComponents($data, $format, $context);
        $populatedRelationContext = $context;
        $relationshipsData = $this->getPopulatedRelations($data, $format, $populatedRelationContext, $allRelationshipsData);

        // Do not include primary resources
        $context['api_included_resources'] = [$context['iri']];

        $includedResourcesData = $this->getRelatedResources($data, $format, $context, $allRelationshipsData);

        $resourceData = [
            'id' => $context['iri'],
            'type' => $this->getResourceShortName($resourceClass),
        ];

        if ($normalizedData) {
            $resourceData['attributes'] = $normalizedData;
        }

        if ($relationshipsData) {
            $resourceData['relationships'] = $relationshipsData;
        }

        $document = [];

        if ($links) {
            $document['links'] = $links;
        }

        $document['data'] = $resourceData;

        if ($includedResourcesData) {
            $document['included'] = $includedResourcesData;
        }

        return $document;
    }
}
