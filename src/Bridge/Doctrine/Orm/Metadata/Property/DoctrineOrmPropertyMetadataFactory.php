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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Metadata\Property;

use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\PropertyInfo\Type;

/**
 * Use Doctrine metadata to populate the identifier property.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DoctrineOrmPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $decorated;
    private $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry, PropertyMetadataFactoryInterface $decorated)
    {
        $this->managerRegistry = $managerRegistry;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
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
        if (!$doctrineClassMetadata) {
            return $propertyMetadata;
        }

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

        foreach ($doctrineClassMetadata->getAssociationMappings() as $associationMapping) {
            if ($associationMapping['fieldName'] === $property) {
                $isNullable = true;
                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    if (!$joinColumn['nullable']) {
                        $isNullable = false;
                        break;
                    }
                }

                $type = $propertyMetadata->getType();
                $newType = new Type(
                    $type->getBuiltinType(),
                    $isNullable,
                    $type->getClassName(),
                    $type->isCollection(),
                    $type->getCollectionKeyType(),
                    $type->getCollectionValueType()
                );

                $propertyMetadata = $propertyMetadata->withType($newType);
            }
        }

        if (null === $propertyMetadata->isIdentifier()) {
            $propertyMetadata = $propertyMetadata->withIdentifier(false);
        }

        return $propertyMetadata;
    }
}
