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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Util;

use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

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
     * @param int|string $id
     *
     * @throws PropertyNotFoundException
     * @throws InvalidIdentifierException
     */
    private function normalizeIdentifiers($id, ObjectManager $manager, string $resourceClass): array
    {
        $identifierValues = [$id];
        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);
        $doctrineIdentifierFields = $doctrineClassMetadata->getIdentifier();
        $isOrm = $manager instanceof EntityManagerInterface;
        $isOdm = $manager instanceof DocumentManager;
        $platform = $isOrm ? $manager->getConnection()->getDatabasePlatform() : null;
        $identifiersMap = null;

        if (\count($doctrineIdentifierFields) > 1) {
            $identifiersMap = [];

            // first transform identifiers to a proper key/value array
            foreach (explode(';', (string) $id) as $identifier) {
                if (!$identifier) {
                    continue;
                }

                $identifierPair = explode('=', $identifier);
                $identifiersMap[$identifierPair[0]] = $identifierPair[1];
            }
        }

        $identifiers = [];
        $i = 0;

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            if (!$propertyMetadata->isIdentifier()) {
                continue;
            }

            $identifier = null === $identifiersMap ? $identifierValues[$i] ?? null : $identifiersMap[$propertyName] ?? null;
            if (null === $identifier) {
                throw new PropertyNotFoundException(sprintf('Invalid identifier "%s", "%s" was not found.', $id, $propertyName));
            }

            $doctrineTypeName = $doctrineClassMetadata->getTypeOfField($propertyName);

            try {
                if ($isOrm && null !== $doctrineTypeName && DBALType::hasType($doctrineTypeName)) {
                    $identifier = DBALType::getType($doctrineTypeName)->convertToPHPValue($identifier, $platform);
                }
                if ($isOdm && null !== $doctrineTypeName && MongoDbType::hasType($doctrineTypeName)) {
                    $identifier = MongoDbType::getType($doctrineTypeName)->convertToPHPValue($identifier);
                }
            } catch (ConversionException $e) {
                throw new InvalidIdentifierException(sprintf('Invalid value "%s" provided for an identifier.', $propertyName), $e->getCode(), $e);
            }

            $identifiers[$propertyName] = $identifier;
            ++$i;
        }

        return $identifiers;
    }
}
