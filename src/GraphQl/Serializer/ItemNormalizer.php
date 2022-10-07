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

namespace ApiPlatform\GraphQl\Serializer;

use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface as LegacyIdentifiersExtractorInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Serializer\ItemNormalizer as BaseItemNormalizer;
use ApiPlatform\Symfony\Security\ResourceAccessCheckerInterface;
use ApiPlatform\Util\ClassInfoTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * GraphQL normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemNormalizer extends BaseItemNormalizer
{
    use ClassInfoTrait;

    public const FORMAT = 'graphql';
    public const ITEM_RESOURCE_CLASS_KEY = '#itemResourceClass';
    public const ITEM_IDENTIFIERS_KEY = '#itemIdentifiers';

    /**
     * @var IdentifiersExtractorInterface|LegacyIdentifiersExtractorInterface
     */
    private $identifiersExtractor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $identifiersExtractor, ResourceClassResolverInterface $resourceClassResolver, PropertyAccessorInterface $propertyAccessor = null, NameConverterInterface $nameConverter = null, ClassMetadataFactoryInterface $classMetadataFactory = null, ItemDataProviderInterface $itemDataProvider = null, bool $allowPlainIdentifiers = false, LoggerInterface $logger = null, iterable $dataTransformers = [], ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, ResourceAccessCheckerInterface $resourceAccessChecker = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $itemDataProvider, $allowPlainIdentifiers, $logger ?: new NullLogger(), $dataTransformers, $resourceMetadataCollectionFactory, $resourceAccessChecker);

        $this->identifiersExtractor = $identifiersExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $context
     *
     * @throws UnexpectedValueException
     *
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $resourceClass = $this->getObjectClass($object);

        if ($outputClass = $this->getOutputClass($resourceClass, $context)) {
            $context['graphql_identifiers'] = [
                self::ITEM_RESOURCE_CLASS_KEY => $context['operation']->getClass(),
                self::ITEM_IDENTIFIERS_KEY => $this->identifiersExtractor->getIdentifiersFromItem($object),
            ];

            return parent::normalize($object, $format, $context);
        }

        unset($context['operation_name'], $context['operation']);
        $data = parent::normalize($object, $format, $context);
        if (!\is_array($data)) {
            throw new UnexpectedValueException('Expected data to be an array.');
        }

        if (isset($context['graphql_identifiers'])) {
            $data = $data + $context['graphql_identifiers'];
        } elseif (!($context['no_resolver_data'] ?? false)) {
            $data[self::ITEM_RESOURCE_CLASS_KEY] = $resourceClass;
            $data[self::ITEM_IDENTIFIERS_KEY] = $this->identifiersExtractor->getIdentifiersFromItem($object, $context['operation'] ?? null);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeCollectionOfRelations($propertyMetadata, $attributeValue, string $resourceClass, ?string $format, array $context): array
    {
        // to-many are handled directly by the GraphQL resolver
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @return array|bool
     */
    protected function getAllowedAttributes($classOrObject, array $context, $attributesAsString = false)
    {
        $allowedAttributes = parent::getAllowedAttributes($classOrObject, $context, $attributesAsString);

        if (($context['api_denormalize'] ?? false) && \is_array($allowedAttributes) && false !== ($indexId = array_search('id', $allowedAttributes, true))) {
            $allowedAttributes[] = '_id';
            array_splice($allowedAttributes, (int) $indexId, 1);
        }

        return $allowedAttributes;
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = []): void
    {
        if ('_id' === $attribute) {
            $attribute = 'id';
        }

        parent::setAttributeValue($object, $attribute, $value, $format, $context);
    }
}
