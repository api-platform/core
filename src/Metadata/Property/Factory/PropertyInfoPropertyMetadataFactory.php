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
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

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

        /* @phpstan-ignore-next-line */
        if (null === $propertyMetadata->isInitializable() && null !== $initializable = $this->propertyInfo->isInitializable($resourceClass, $property, $options)) {
            $propertyMetadata = $propertyMetadata->withInitializable($initializable);
        }

        return $propertyMetadata;
    }
}
