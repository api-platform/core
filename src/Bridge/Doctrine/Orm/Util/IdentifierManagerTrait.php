<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Util;

use ApiPlatform\Core\Exception\PropertyNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * @internal
 */
trait IdentifierManagerTrait
{
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;

    /**
     * Transform and check the identifier, composite or not.
     *
     * @param int|string    $id
     * @param ObjectManager $manager
     * @param string        $resourceClass
     *
     * @throws PropertyNotFoundException
     *
     * @return array
     */
    public function normalizeIdentifiers($id, ObjectManager $manager, string $resourceClass): array
    {
        $identifierValues = [$id];
        $doctrineMetadataIdentifier = $manager->getClassMetadata($resourceClass)->getIdentifier();

        if (2 <= count($doctrineMetadataIdentifier)) {
            $identifiers = explode(';', $id);
            $identifiersMap = [];

            // first transform identifiers to a proper key/value array
            foreach ($identifiers as $identifier) {
                $keyValue = explode('=', $identifier);
                $identifiersMap[$keyValue[0]] = $keyValue[1];
            }
        }

        $identifiers = [];
        $i = 0;

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            $identifier = $propertyMetadata->isIdentifier();
            if (null === $identifier || false === $identifier) {
                continue;
            }

            $identifier = !isset($identifiersMap) ? $identifierValues[$i] ?? null : $identifiersMap[$propertyName] ?? null;
            if (null === $identifier) {
                throw new PropertyNotFoundException(sprintf('Invalid identifier "%s", "%s" has not been found.', $id, $propertyName));
            }

            $identifiers[$propertyName] = $identifier;
            ++$i;
        }

        return $identifiers;
    }
}
