<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Metadata\Property;

use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

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
    public function create(string $resourceClass, string $property, array $options = []) : PropertyMetadata
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

        if ($doctrineClassMetadata->isInheritanceTypeSingleTable() || $doctrineClassMetadata->isInheritanceTypeJoined()) {
            // Keep a reference to the processed discriminatorMap to avoid infinite looping over joined tables
            // the ClassMetadata discriminatorMap property for joined inheritance entities holds every class even for the child entity
            // in case of the single table inheritance, it might hold the current ResourceClass, we don't need to parse it twice
            if (!isset($options['discriminatorMap'])) {
                $options['discriminatorMap'] = [$resourceClass];
            }

            foreach ($doctrineClassMetadata->discriminatorMap as $childEntity) {
                if (in_array($childEntity, $options['discriminatorMap']) || !$this->classHasProperty($childEntity, $property)) {
                    continue;
                }

                try {
                    $options['discriminatorMap'][] = $childEntity;
                    $propertyMetadata = $this->create($childEntity, $property, $options);
                    $propertyMetadata = $propertyMetadata->withInheritance($childEntity);
                    break;
                } catch (PropertyNotFoundException $resourceClassNotFoundException) {
                    // Ignore not found exceptions
                }
            }
        }

        $identifiers = $doctrineClassMetadata->getIdentifier();
        foreach ($identifiers as $identifier) {
            if ($identifier === $property) {
                $propertyMetadata = $propertyMetadata->withIdentifier(true);
                if ($doctrineClassMetadata instanceof ClassMetadataInfo) {
                    $writable = $doctrineClassMetadata->isIdentifierNatural();
                } else {
                    $writable = false;
                }

                $propertyMetadata = $propertyMetadata->withWritable($writable);

                break;
            }
        }

        if (null === $propertyMetadata->isIdentifier()) {
            $propertyMetadata = $propertyMetadata->withIdentifier(false);
        }

        return $propertyMetadata;
    }

    private function classHasProperty($resourceClass, $property)
    {
        try {
            $refl = new \ReflectionClass($resourceClass);
            $refl->getProperty($property);
        } catch (\ReflectionException $e) {
            return false;
        }

        return true;
    }
}
