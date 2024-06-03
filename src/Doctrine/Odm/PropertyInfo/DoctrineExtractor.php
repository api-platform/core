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

namespace ApiPlatform\Doctrine\Odm\PropertyInfo;

use ApiPlatform\Metadata\Util\PropertyInfoToTypeInfoHelper;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDbClassMetadata;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;

/**
 * Extracts data using Doctrine MongoDB ODM metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class DoctrineExtractor implements PropertyListExtractorInterface, PropertyTypeExtractorInterface, PropertyAccessExtractorInterface
{
    public function __construct(private readonly ObjectManager $objectManager)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]|null
     */
    public function getProperties($class, array $context = []): ?array
    {
        if (null === $metadata = $this->getMetadata($class)) {
            return null;
        }

        return $metadata->getFieldNames();
    }

    /**
     * {@inheritdoc}
     *
     * @return LegacyType[]|null
     */
    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        if (null === $metadata = $this->getMetadata($class)) {
            return null;
        }

        if ($metadata->hasAssociation($property)) {
            /** @var class-string|null */
            $class = $metadata->getAssociationTargetClass($property);

            if (null === $class) {
                return null;
            }

            if ($metadata->isSingleValuedAssociation($property)) {
                $nullable = $metadata instanceof MongoDbClassMetadata && $metadata->isNullable($property);

                return [new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, $nullable, $class)];
            }

            $collectionKeyType = LegacyType::BUILTIN_TYPE_INT;

            return [
                new LegacyType(
                    LegacyType::BUILTIN_TYPE_OBJECT,
                    false,
                    Collection::class,
                    true,
                    new LegacyType($collectionKeyType),
                    new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, false, $class)
                ),
            ];
        }

        if ($metadata->hasField($property)) {
            $typeOfField = $metadata->getTypeOfField($property);
            $nullable = $metadata instanceof MongoDbClassMetadata && $metadata->isNullable($property);
            $enumType = null;
            if (null !== $enumClass = $metadata instanceof MongoDbClassMetadata ? $metadata->getFieldMapping($property)['enumType'] ?? null : null) {
                $enumType = new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, $nullable, $enumClass);
            }

            switch ($typeOfField) {
                case MongoDbType::DATE:
                    return [new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, $nullable, \DateTime::class)];
                case MongoDbType::DATE_IMMUTABLE:
                    return [new LegacyType(LegacyType::BUILTIN_TYPE_OBJECT, $nullable, \DateTimeImmutable::class)];
                case MongoDbType::HASH:
                    return [new LegacyType(LegacyType::BUILTIN_TYPE_ARRAY, $nullable, null, true)];
                case MongoDbType::COLLECTION:
                    return [new LegacyType(LegacyType::BUILTIN_TYPE_ARRAY, $nullable, null, true, new LegacyType(LegacyType::BUILTIN_TYPE_INT))];
                case MongoDbType::INT:
                case MongoDbType::STRING:
                    if ($enumType) {
                        return [$enumType];
                    }
            }

            $builtinType = $this->getPhpType($typeOfField);

            return $builtinType ? [new LegacyType($builtinType, $nullable)] : null;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($class, $property, array $context = []): ?bool
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($class, $property, array $context = []): ?bool
    {
        if (
            null === ($metadata = $this->getMetadata($class))
            || ($metadata instanceof MongoDbClassMetadata && MongoDbClassMetadata::GENERATOR_TYPE_NONE === $metadata->generatorType)
            || !\in_array($property, $metadata->getIdentifierFieldNames(), true)
        ) {
            return null;
        }

        return false;
    }

    private function getMetadata(string $class): ?ClassMetadata
    {
        try {
            return $this->objectManager->getClassMetadata($class);
        } catch (MappingException) {
            return null;
        }
    }

    public function getType(string $class, string $property, array $context = []): ?Type
    {
        return PropertyInfoToTypeInfoHelper::convertLegacyTypesToType($this->getTypes($class, $property, $context));
    }

    /**
     * Gets the corresponding built-in PHP type.
     */
    private function getPhpType(string $doctrineType): ?string
    {
        return match ($doctrineType) {
            MongoDbType::INTEGER, MongoDbType::INT, MongoDbType::INTID, MongoDbType::KEY => LegacyType::BUILTIN_TYPE_INT,
            MongoDbType::FLOAT => LegacyType::BUILTIN_TYPE_FLOAT,
            MongoDbType::STRING, MongoDbType::ID, MongoDbType::OBJECTID, MongoDbType::TIMESTAMP, MongoDbType::BINDATA, MongoDbType::BINDATABYTEARRAY, MongoDbType::BINDATACUSTOM, MongoDbType::BINDATAFUNC, MongoDbType::BINDATAMD5, MongoDbType::BINDATAUUID, MongoDbType::BINDATAUUIDRFC4122 => LegacyType::BUILTIN_TYPE_STRING,
            MongoDbType::BOOLEAN, MongoDbType::BOOL => LegacyType::BUILTIN_TYPE_BOOL,
            default => null,
        };
    }
}
