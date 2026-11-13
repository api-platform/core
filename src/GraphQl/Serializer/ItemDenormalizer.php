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

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\ItemNormalizerTrait;
use ApiPlatform\Serializer\OperationResourceClassResolverInterface;
use ApiPlatform\Serializer\TagCollectorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts GraphQL inputs to objects (denormalization only).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemDenormalizer extends AbstractItemNormalizer
{
    use ItemNormalizerTrait;

    public const FORMAT = 'graphql';

    private readonly LoggerInterface $logger;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ?PropertyAccessorInterface $propertyAccessor = null, ?NameConverterInterface $nameConverter = null, ?ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], ?ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory = null, ?ResourceAccessCheckerInterface $resourceAccessChecker = null, ?TagCollectorInterface $tagCollector = null, ?OperationResourceClassResolverInterface $operationResourceResolver = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $defaultContext, $resourceMetadataCollectionFactory, $resourceAccessChecker, $tagCollector, $operationResourceResolver);

        $this->logger = $logger ?? new NullLogger();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return false;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? parent::getSupportedTypes($format) : [];
    }

    protected function getAllowedAttributes(string|object $classOrObject, array $context, bool $attributesAsString = false): array|bool
    {
        $allowedAttributes = parent::getAllowedAttributes($classOrObject, $context, $attributesAsString);

        if (($context['api_denormalize'] ?? false) && \is_array($allowedAttributes) && false !== ($indexId = array_search('id', $allowedAttributes, true))) {
            $allowedAttributes[] = '_id';
            array_splice($allowedAttributes, (int) $indexId, 1);
        }

        return $allowedAttributes;
    }

    protected function setAttributeValue(object $object, string $attribute, mixed $value, ?string $format = null, array $context = []): void
    {
        if ('_id' === $attribute) {
            $attribute = 'id';
        }

        parent::setAttributeValue($object, $attribute, $value, $format, $context);
    }
}
