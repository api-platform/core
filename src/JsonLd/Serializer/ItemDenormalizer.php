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

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\SubtypeResolverInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use ApiPlatform\Serializer\OperationResourceClassResolverInterface;
use ApiPlatform\Serializer\TagCollectorInterface;
use ApiPlatform\State\Exception\UnprocessableValueException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Converts JSON-LD data to objects (denormalization only).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ItemDenormalizer extends AbstractItemNormalizer
{
    use ItemNormalizerTrait {
        denormalize as private denormalizeItem;
    }

    public const FORMAT = 'jsonld';

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, IriConverterInterface $iriConverter, ResourceClassResolverInterface $resourceClassResolver, ?PropertyAccessorInterface $propertyAccessor = null, ?NameConverterInterface $nameConverter = null, ?ClassMetadataFactoryInterface $classMetadataFactory = null, array $defaultContext = [], ?ResourceAccessCheckerInterface $resourceAccessChecker = null, protected ?TagCollectorInterface $tagCollector = null, ?OperationResourceClassResolverInterface $operationResourceResolver = null, ?SubtypeResolverInterface $subtypeResolver = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $iriConverter, $resourceClassResolver, $propertyAccessor, $nameConverter, $classMetadataFactory, $defaultContext, $resourceMetadataCollectionFactory, $resourceAccessChecker, $tagCollector, $operationResourceResolver, $subtypeResolver);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return false;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $object = $this->denormalizeItem($data, $type, $format, $context);

        if (null !== $this->subtypeResolver && \is_array($data) && isset($data['@type']) && $this->resourceClassResolver->isResourceClass($type)) {
            $resourceClass = $this->resourceClassResolver->getResourceClass(null, $type);
            if ($object instanceof $resourceClass) {
                $this->validateType((array) $data['@type'], $object, $resourceClass, $context);
            }
        }

        return $object;
    }

    /**
     * A "@type" naming a class of the discriminator maps the object is not an instance of contradicts its type property.
     *
     * @param array<mixed> $types
     */
    private function validateType(array $types, object $object, string $resourceClass, array $context): void
    {
        $classes = [$resourceClass, ...$this->subtypeResolver->getSubtypes($resourceClass)];
        $classesByName = [];
        foreach ($classes as $class) {
            foreach ($this->subtypeResolver->getTypes($class) as $typeName) {
                $classesByName[$typeName][] = $class;
            }
        }

        foreach ($types as $typeName) {
            if (!\is_string($typeName) || !isset($classesByName[$typeName])) {
                continue;
            }

            foreach ($classesByName[$typeName] as $class) {
                if ($object instanceof $class) {
                    continue 2;
                }
            }

            // The map deciding between both classes is the one of their most specific common mapped parent
            $namedClass = $classesByName[$typeName][0];
            $decidingClass = $resourceClass;
            foreach ($classes as $class) {
                if ($object instanceof $class && is_a($namedClass, $class, true) && is_a($class, $decidingClass, true) && null !== $this->subtypeResolver->getMapping($class)) {
                    $decidingClass = $class;
                }
            }

            $typeProperty = $this->subtypeResolver->getMapping($decidingClass)?->getTypeProperty() ?? '@type';

            throw new UnprocessableValueException(\sprintf('The type "%s" does not match the "%s" property.', $typeName, $typeProperty), $typeName, isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$typeProperty : $typeProperty, UnprocessableValueException::TYPE_MISMATCH_ERROR);
        }
    }

    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? parent::getSupportedTypes($format) : [];
    }
}
