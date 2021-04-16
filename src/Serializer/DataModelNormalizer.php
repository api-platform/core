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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Data model normalizer.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class DataModelNormalizer extends ObjectNormalizer
{
    private $resourceMetadataFactory;
    private $identifiersExtractor;
    private $itemDataProvider;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, IdentifiersExtractorInterface $identifiersExtractor, ItemDataProviderInterface $itemDataProvider, ClassMetadataFactoryInterface $classMetadataFactory = null, NameConverterInterface $nameConverter = null, PropertyAccessorInterface $propertyAccessor = null, PropertyTypeExtractorInterface $propertyTypeExtractor = null, ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null, callable $objectClassResolver = null, array $defaultContext = [])
    {
        parent::__construct(
            $classMetadataFactory,
            $nameConverter,
            $propertyAccessor,
            $propertyTypeExtractor,
            $classDiscriminatorResolver,
            $objectClassResolver,
            $defaultContext
        );

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->itemDataProvider = $itemDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        if (!parent::supportsDenormalization($data, $type, $format)) {
            return false;
        }

        if (!\is_array($data)) {
            return false;
        }

        if (!isset($data[MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY])) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        unset($data[MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY]);

        return parent::denormalize($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = []): void
    {
        if (\is_array($value) && \count($value) > 0) {
            if (isset($value[MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY])) {
                $value = $this->denormalizeChildMappedDataModel($attribute, $value, $format, $context);
            } elseif ($this->isMappedDataModelCollection($value)) {
                $collection = [];
                foreach ($value as $mappedDataModel) {
                    $collection[] = $this->denormalizeChildMappedDataModel($attribute, $mappedDataModel, $format, $context);
                }
                $value = $collection;
            }
        }

        try {
            $this->propertyAccessor->setValue($object, $attribute, $value);
        } catch (NoSuchPropertyException $exception) {
            // Properties not found are ignored
        }
    }

    /**
     * @return object|null
     */
    private function getModel(array $data, string $resourceClass, string $modelClass, array $context)
    {
        $identifiers = $this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass);

        $ids = [];
        foreach ($identifiers as $identifier) {
            if (isset($data[$identifier])) {
                $ids[$identifier] = $data[$identifier];
            }
        }

        return $this->itemDataProvider->getItem($modelClass, $ids, null, $context);
    }

    /**
     * @return mixed|object
     */
    private function denormalizeChildMappedDataModel(string $attribute, array $value, ?string $format, array $context)
    {
        $resourceClass = $value[MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY];
        unset($value[MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY]);
        $modelClass = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('data_model');

        if (!$modelClass) {
            throw new NotNormalizableValueException(sprintf('Class "%s" should have a data_model attribute in ApiResource.', $resourceClass));
        }

        $model = $this->getModel($value, $resourceClass, $modelClass, $context);

        $childContext = $this->createChildContext($context, $attribute, $format);
        $childContext[AbstractNormalizer::OBJECT_TO_POPULATE] = $model;

        return parent::denormalize($value, $modelClass, $format, $childContext);
    }

    private function isMappedDataModelCollection(array $value): bool
    {
        foreach ($value as $element) {
            if (!\is_array($element)) {
                return false;
            }

            if (!isset($element[MappedDataModelNormalizer::ITEM_RESOURCE_CLASS_KEY])) {
                return false;
            }
        }

        return true;
    }
}
