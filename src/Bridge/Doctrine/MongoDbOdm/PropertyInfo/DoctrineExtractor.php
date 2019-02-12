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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\PropertyInfo;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Extracts data using Doctrine MongoDB ODM metadata.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class DoctrineExtractor implements PropertyListExtractorInterface, PropertyTypeExtractorInterface
{
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($class, array $context = [])
    {
        try {
            $metadata = $this->objectManager->getClassMetadata($class);
        } catch (MappingException $exception) {
            return null;
        }

        return $metadata->getFieldNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($class, $property, array $context = [])
    {
        try {
            $metadata = $this->objectManager->getClassMetadata($class);
        } catch (MappingException $exception) {
            return null;
        }

        $reflectionMetadata = new \ReflectionClass($metadata);

        if ($metadata->hasAssociation($property)) {
            $class = $metadata->getAssociationTargetClass($property);

            if ($metadata->isSingleValuedAssociation($property)) {
                if ($reflectionMetadata->hasMethod('isNullable')) {
                    $nullable = $metadata->isNullable($property);
                } else {
                    $nullable = false;
                }

                return [new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, $class)];
            }

            $collectionKeyType = Type::BUILTIN_TYPE_INT;

            return [
                new Type(
                    Type::BUILTIN_TYPE_OBJECT,
                    false,
                    Collection::class,
                    true,
                    new Type($collectionKeyType),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, $class)
                ),
            ];
        }

        if ($metadata->hasField($property)) {
            $typeOfField = $metadata->getTypeOfField($property);
            $nullable = $reflectionMetadata->hasMethod('isNullable') && $metadata->isNullable($property);

            switch ($typeOfField) {
                case MongoDbType::DATE:
                    return [new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, 'DateTime')];
                case MongoDbType::HASH:
                    return [new Type(Type::BUILTIN_TYPE_ARRAY, $nullable, null, true)];
                case MongoDbType::COLLECTION:
                    return [new Type(Type::BUILTIN_TYPE_ARRAY, $nullable, null, true, new Type(Type::BUILTIN_TYPE_INT))];
                default:
                    $builtinType = $this->getPhpType($typeOfField);

                    return $builtinType ? [new Type($builtinType, $nullable)] : null;
            }
        }
    }

    /**
     * Gets the corresponding built-in PHP type.
     */
    private function getPhpType(string $doctrineType): ?string
    {
        switch ($doctrineType) {
            case MongoDbType::INTEGER:
            case MongoDbType::INT:
            case MongoDbType::INTID:
            case MongoDbType::KEY:
                return Type::BUILTIN_TYPE_INT;
            case MongoDbType::FLOAT:
                return Type::BUILTIN_TYPE_FLOAT;
            case MongoDbType::STRING:
            case MongoDbType::ID:
            case MongoDbType::OBJECTID:
            case MongoDbType::TIMESTAMP:
            case MongoDbType::BINDATA:
            case MongoDbType::BINDATABYTEARRAY:
            case MongoDbType::BINDATACUSTOM:
            case MongoDbType::BINDATAFUNC:
            case MongoDbType::BINDATAMD5:
            case MongoDbType::BINDATAUUID:
            case MongoDbType::BINDATAUUIDRFC4122:
                return Type::BUILTIN_TYPE_STRING;
            case MongoDbType::BOOLEAN:
            case MongoDbType::BOOL:
                return Type::BUILTIN_TYPE_BOOL;
        }

        return null;
    }
}
