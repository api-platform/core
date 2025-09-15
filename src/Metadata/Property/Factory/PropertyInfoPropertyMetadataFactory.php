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

namespace ApiPlatform\Metadata\Property\Factory;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * PropertyInfo metadata loader decorator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PropertyInfoPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(private readonly PropertyInfoExtractorInterface $propertyInfo, private readonly ?PropertyMetadataFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        if (null === $this->decorated) {
            $propertyMetadata = new ApiProperty();
        } else {
            try {
                $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException) {
                $propertyMetadata = new ApiProperty();
            }
        }

        // TODO: remove in 5.x
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            if (!$propertyMetadata->getBuiltinTypes()) {
                $types = $this->propertyInfo->getTypes($resourceClass, $property, $options) ?? [];

                foreach ($types as $i => $type) {
                    // Temp fix for https://github.com/symfony/symfony/pull/52699
                    if (ArrayCollection::class === $type->getClassName()) {
                        $types[$i] = new Type($type->getBuiltinType(), $type->isNullable(), $type->getClassName(), true, $type->getCollectionKeyTypes(), $type->getCollectionValueTypes());
                    }
                }

                $propertyMetadata = $propertyMetadata->withBuiltinTypes($types);
            }
        } else {
            if (!$propertyMetadata->getNativeType()) {
                $propertyMetadata = $propertyMetadata->withNativeType($this->propertyInfo->getType($resourceClass, $property, $options));
            }
        }

        if (null === $propertyMetadata->getDescription() && null !== $description = $this->propertyInfo->getShortDescription($resourceClass, $property, $options)) {
            $propertyMetadata = $propertyMetadata->withDescription($description);
        }

        if (null === $propertyMetadata->isReadable() && null !== $readable = $this->propertyInfo->isReadable($resourceClass, $property, $options)) {
            $propertyMetadata = $propertyMetadata->withReadable($readable);
        }

        // A property might not be writable and we can still want to update it,
        // this leaves the choice to the SerializerPropertyMetadataFactory
        if (false === ($options['api_allow_update'] ?? false) && null === $propertyMetadata->isWritable() && null !== $writable = $this->propertyInfo->isWritable($resourceClass, $property, $options)) {
            $propertyMetadata = $propertyMetadata->withWritable($writable);
        }

        /* @phpstan-ignore-next-line */
        if (null === $propertyMetadata->isInitializable() && null !== $initializable = $this->propertyInfo->isInitializable($resourceClass, $property, $options)) {
            $propertyMetadata = $propertyMetadata->withInitializable($initializable);
        }

        return $propertyMetadata;
    }
}
