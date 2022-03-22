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

use ApiPlatform\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

/**
 * PropertyInfo metadata loader decorator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PropertyInfoPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $propertyInfo;
    private $decorated;

    public function __construct(PropertyInfoExtractorInterface $propertyInfo, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->propertyInfo = $propertyInfo;
        $this->decorated = $decorated;
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
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                $propertyMetadata = new ApiProperty();
            }
        }

        if (!$propertyMetadata->getBuiltinTypes()) {
            $propertyMetadata = $propertyMetadata->withBuiltinTypes($this->propertyInfo->getTypes($resourceClass, $property, $options) ?? []);
        }

        if (null === $propertyMetadata->getDescription() && null !== $description = $this->propertyInfo->getShortDescription($resourceClass, $property, $options)) {
            $propertyMetadata = $propertyMetadata->withDescription($description);
        }

        if (null === $propertyMetadata->isReadable() && null !== $readable = $this->propertyInfo->isReadable($resourceClass, $property, $options)) {
            $propertyMetadata = $propertyMetadata->withReadable($readable);
        }

        if (null === $propertyMetadata->isWritable() && null !== $writable = $this->propertyInfo->isWritable($resourceClass, $property, $options)) {
            $propertyMetadata = $propertyMetadata->withWritable($writable);
        }

        if (method_exists($this->propertyInfo, 'isInitializable')) {
            if (null === $propertyMetadata->isInitializable() && null !== $initializable = $this->propertyInfo->isInitializable($resourceClass, $property, $options)) {
                $propertyMetadata = $propertyMetadata->withInitializable($initializable);
            }
        } else {
            // BC layer for Symfony < 4.2
            $ref = new \ReflectionClass($resourceClass);
            if ($ref->isInstantiable() && $constructor = $ref->getConstructor()) {
                foreach ($constructor->getParameters() as $constructorParameter) {
                    if ($constructorParameter->name === $property && null === $propertyMetadata->isInitializable()) {
                        $propertyMetadata = $propertyMetadata->withInitializable(true);
                    }
                }
            }
        }

        return $propertyMetadata;
    }
}
