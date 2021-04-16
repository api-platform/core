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

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Mapped data model resource normalizer.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class MappedDataModelNormalizer extends ObjectNormalizer implements ContextAwareDenormalizerInterface, ContextAwareNormalizerInterface
{
    use ClassInfoTrait;

    public const MAPPED_DATA_MODEL = 'mapped_data_model';
    public const ITEM_RESOURCE_CLASS_KEY = '#itemResourceClass';

    private $resourceClassResolver;
    private $propertyMetadataFactory;

    public function __construct(ClassMetadataFactoryInterface $classMetadataFactory = null, NameConverterInterface $nameConverter = null, PropertyAccessorInterface $propertyAccessor = null, PropertyTypeExtractorInterface $propertyTypeExtractor = null, ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null, callable $objectClassResolver = null, array $defaultContext = [], ResourceClassResolverInterface $resourceClassResolver = null, PropertyMetadataFactoryInterface $propertyMetadataFactory = null)
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

        $this->resourceClassResolver = $resourceClassResolver;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        if (!parent::supportsNormalization($data, $format)) {
            return false;
        }

        if (!($context[self::MAPPED_DATA_MODEL] ?? false)) {
            return false;
        }

        if (!$this->resourceClassResolver) {
            return false;
        }

        return $this->resourceClassResolver->isResourceClass($this->getObjectClass($data));
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $context[self::MAPPED_DATA_MODEL] = true;
        $resourceClass = $this->resourceClassResolver->getResourceClass($object, $context['resource_class'] ?? null);
        $context['resource_class'] = $resourceClass;

        $data = parent::normalize($object, $format, $context);
        $data[self::ITEM_RESOURCE_CLASS_KEY] = $resourceClass;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        if (!parent::supportsDenormalization($data, $type, $format)) {
            return false;
        }

        if (!($context[self::MAPPED_DATA_MODEL] ?? false)) {
            return false;
        }

        if (!$this->resourceClassResolver) {
            return false;
        }

        return $this->resourceClassResolver->isResourceClass($type);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $context[self::MAPPED_DATA_MODEL] = true;
        $context['resource_class'] = $this->resourceClassResolver->getResourceClass(null, $type);

        return parent::denormalize($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllowedAttribute($classOrObject, $attribute, $format = null, array $context = []): bool
    {
        if (!parent::isAllowedAttribute($classOrObject, $attribute, $format, $context)) {
            return false;
        }

        $propertyMetadata = $this->propertyMetadataFactory->create($context['resource_class'], $attribute);
        if ($propertyMetadata->getAttribute('virtual', false)) {
            return false;
        }

        return true;
    }
}
