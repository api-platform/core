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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Fetch inherited properties in case of a SINGLE_TABLE and JOINED doctrine inheritance types.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class DoctrineOrmPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    private $reader;
    private $decorated;
    private $reflection;

    public function __construct(ManagerRegistry $managerRegistry, PropertyNameCollectionFactoryInterface $decorated = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []) : PropertyNameCollection
    {
        if ($this->decorated) {
            try {
                $propertyNameCollection = $this->decorated->create($resourceClass, $options);
            } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                // Ignore not found exceptions from parent
            }
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager) {
            return $propertyNameCollection;
        }

        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);
        if (!$doctrineClassMetadata) {
            return $propertyNameCollection;
        }

        if ($doctrineClassMetadata->isInheritanceTypeNone()) {
            return $propertyNameCollection;
        }

        $propertyNames = [];

        if ($doctrineClassMetadata->isInheritanceTypeSingleTable() || $doctrineClassMetadata->isInheritanceTypeJoined()) {
            // Keep a reference to the processed discriminatorMap to avoid infinite looping over joined tables
            // the ClassMetadata discriminatorMap property for joined inheritance entities holds every class even for the child entity
            // in case of the single table inheritance, it might hold the current ResourceClass, we don't need to parse it twice
            if (!isset($options['discriminatorMap'])) {
                $options['discriminatorMap'] = [$resourceClass];
            }

            foreach ($doctrineClassMetadata->discriminatorMap as $childEntity) {
                if (in_array($childEntity, $options['discriminatorMap'])) {
                    continue;
                }

                try {
                    $options['discriminatorMap'][] = $childEntity;
                    foreach ($this->create($childEntity, $options) as $key => $childPropertyName) {
                        $propertyNames[$childPropertyName] = true;
                    }
                } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                    // Ignore not found exceptions
                }
            }
        }

        // Inherited from parent
        if (isset($propertyNameCollection)) {
            foreach ($propertyNameCollection as $propertyName) {
                $propertyNames[$propertyName] = true;
            }
        }

        return new PropertyNameCollection(array_keys($propertyNames));
    }
}
