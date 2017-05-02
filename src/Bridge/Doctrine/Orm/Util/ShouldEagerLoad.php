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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Util;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @internal
 */
trait ShouldEagerLoad
{
    /**
     * Checks if an operation has a `force_eager` attribute.
     *
     * @param string $resourceClass
     * @param array  $options
     *
     * @return bool
     */
    private function shouldOperationForceEager(string $resourceClass, array $options): bool
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if (isset($options['collection_operation_name'])) {
            $forceEager = $resourceMetadata->getCollectionOperationAttribute($options['collection_operation_name'], 'force_eager', null, true);
        } elseif (isset($options['item_operation_name'])) {
            $forceEager = $resourceMetadata->getItemOperationAttribute($options['item_operation_name'], 'force_eager', null, true);
        } else {
            $forceEager = $resourceMetadata->getAttribute('force_eager');
        }

        return is_bool($forceEager) ? $forceEager : $this->forceEager;
    }

    /**
     * Checkes if the class has an associationMapping with FETCH=EAGER.
     *
     * @param EntityManager     $em
     * @param ClassMetadataInfo $classMetadata
     * @param array             $checked array cache of tested metadata classes
     *
     * @return bool
     */
    private function hasFetchEagerAssociation(EntityManager $em, ClassMetadataInfo $classMetadata, array &$checked = []): bool
    {
        $checked[] = $classMetadata->name;

        foreach ($classMetadata->associationMappings as $mapping) {
            if (ClassMetadataInfo::FETCH_EAGER === $mapping['fetch']) {
                return true;
            }

            $related = $em->getClassMetadata($mapping['targetEntity']);

            if (in_array($related->name, $checked, true)) {
                continue;
            }

            if (true === $this->hasFetchEagerAssociation($em, $related, $checked)) {
                return true;
            }
        }

        return false;
    }
}
