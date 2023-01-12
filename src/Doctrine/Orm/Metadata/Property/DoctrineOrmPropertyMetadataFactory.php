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

namespace ApiPlatform\Doctrine\Orm\Metadata\Property;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Use Doctrine metadata to populate the identifier property.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DoctrineOrmPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry, private readonly PropertyMetadataFactoryInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        if (null !== $propertyMetadata->isIdentifier()) {
            return $propertyMetadata;
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager) {
            return $propertyMetadata;
        }
        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);

        $identifiers = $doctrineClassMetadata->getIdentifier();
        foreach ($identifiers as $identifier) {
            if ($identifier === $property) {
                $propertyMetadata = $propertyMetadata->withIdentifier(true);

                if (null !== $propertyMetadata->isWritable()) {
                    break;
                }

                if ($doctrineClassMetadata instanceof ClassMetadataInfo) {
                    $writable = $doctrineClassMetadata->isIdentifierNatural();
                } else {
                    $writable = false;
                }

                $propertyMetadata = $propertyMetadata->withWritable($writable);

                break;
            }
        }

        if ($doctrineClassMetadata instanceof ClassMetadataInfo && \in_array($property, $doctrineClassMetadata->getFieldNames(), true)) {
            /** @var mixed[] */
            $fieldMapping = $doctrineClassMetadata->getFieldMapping($property);
            $propertyMetadata = $propertyMetadata->withDefault($fieldMapping['options']['default'] ?? $propertyMetadata->getDefault());
        }

        return $propertyMetadata;
    }
}
