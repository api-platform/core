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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\LoggerAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\LoggerAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\PropertyHelperTrait as OrmPropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * @internal
 */
class AbstractUuidFilter implements FilterInterface, ManagerRegistryAwareInterface, JsonSchemaFilterInterface, OpenApiParameterFilterInterface, LoggerAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use LoggerAwareTrait;
    use ManagerRegistryAwareTrait;
    use OrmPropertyHelperTrait;
    use PropertyHelperTrait;

    private const UUID_SCHEMA = [
        'type' => 'string',
        'format' => 'uuid',
    ];

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'] ?? null;
        if (!$parameter) {
            return;
        }

        $this->filterProperty($parameter->getProperty(), $parameter->getValue(), $queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
    }

    private function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        $associations = [];
        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasField($field)) {
            $value = $this->convertValuesToTheDatabaseRepresentation($queryBuilder, $this->getDoctrineFieldType($property, $resourceClass), $value);
            $this->addWhere($queryBuilder, $queryNameGenerator, $alias, $field, $value);

            return;
        }

        // metadata doesn't have the field, nor an association on the field
        if (!$metadata->hasAssociation($field)) {
            $this->logger->notice('Tried to filter on a non-existent field or association', [
                'field' => $field,
                'resource_class' => $resourceClass,
                'exception' => new InvalidArgumentException(\sprintf('Property "%s" does not exist in resource "%s".', $field, $resourceClass)),
            ]);

            return;
        }

        // association, let's fetch the entity (or reference to it) if we can so we can make sure we get its orm id
        $associationResourceClass = $metadata->getAssociationTargetClass($field);
        $associationMetadata = $this->getClassMetadata($associationResourceClass);
        $associationFieldIdentifier = $associationMetadata->getIdentifierFieldNames()[0];
        $doctrineTypeField = $this->getDoctrineFieldType($associationFieldIdentifier, $associationResourceClass);

        $associationAlias = $alias;
        $associationField = $field;

        if ($metadata->isCollectionValuedAssociation($associationField) || $metadata->isAssociationInverseSide($field)) {
            $associationAlias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $alias, $associationField);
            $associationField = $associationFieldIdentifier;
        }

        $value = $this->convertValuesToTheDatabaseRepresentation($queryBuilder, $doctrineTypeField, $value);
        $this->addWhere($queryBuilder, $queryNameGenerator, $associationAlias, $associationField, $value);
    }

    /**
     * Converts value to their database representation.
     */
    private function convertValuesToTheDatabaseRepresentation(QueryBuilder $queryBuilder, ?string $doctrineFieldType, mixed $value): mixed
    {
        if (null === $doctrineFieldType || !Type::hasType($doctrineFieldType)) {
            throw new InvalidArgumentException(\sprintf('The Doctrine type "%s" is not valid or not registered.', $doctrineFieldType));
        }

        $doctrineType = Type::getType($doctrineFieldType);
        $platform = $queryBuilder->getEntityManager()->getConnection()->getDatabasePlatform();

        $convertValue = static function (mixed $value) use ($doctrineType, $platform) {
            try {
                return $doctrineType->convertToDatabaseValue($value, $platform);
            } catch (ConversionException $e) {
                throw new InvalidArgumentException(\sprintf('The value "%s" could not be converted to database representation.', $value), previous: $e);
            }
        };

        if (\is_array($value)) {
            return array_map($convertValue, $value);
        }

        return $convertValue($value);
    }

    /**
     * Adds where clause.
     */
    private function addWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $field, mixed $value): void
    {
        $valueParameter = ':'.$queryNameGenerator->generateParameterName($field);
        $aliasedField = \sprintf('%s.%s', $alias, $field);

        if (!\is_array($value)) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq($aliasedField, $valueParameter))
                ->setParameter($valueParameter, $value, $this->getDoctrineParameterType());

            return;
        }

        $queryBuilder
            ->andWhere($queryBuilder->expr()->in($aliasedField, $valueParameter))
            ->setParameter($valueParameter, $value, $this->getDoctrineArrayParameterType());
    }

    protected function getDoctrineParameterType(): ?ParameterType
    {
        return null;
    }

    protected function getDoctrineArrayParameterType(): ?ArrayParameterType
    {
        return null;
    }

    public function getOpenApiParameters(Parameter $parameter): OpenApiParameter|array|null
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';
        $schema = $parameter->getSchema();
        $isArraySchema = 'array' === ($schema['type'] ?? null);
        $hasNonArrayType = isset($schema['type']) && 'array' !== $schema['type'];

        // Get filter's base schema
        $baseSchema = self::UUID_SCHEMA;
        $arraySchema = ['type' => 'array', 'items' => $baseSchema];

        if ($isArraySchema) {
            return new OpenApiParameter(
                name: $parameter->getKey().'[]',
                in: $in,
                schema: $arraySchema,
                style: 'deepObject',
                explode: true,
            );
        }

        if ($hasNonArrayType) {
            return new OpenApiParameter(
                name: $parameter->getKey(),
                in: $in,
                schema: $baseSchema,
            );
        }

        // oneOf or no specific type constraint - return both with explicit schemas
        return [
            new OpenApiParameter(
                name: $parameter->getKey(),
                in: $in,
                schema: $baseSchema,
            ),
            new OpenApiParameter(
                name: $parameter->getKey().'[]',
                in: $in,
                schema: $arraySchema,
                style: 'deepObject',
                explode: true,
            ),
        ];
    }

    public function getSchema(Parameter $parameter): array
    {
        if (false === $parameter->getCastToArray()) {
            return self::UUID_SCHEMA;
        }

        return [
            'oneOf' => [
                self::UUID_SCHEMA,
                [
                    'type' => 'array',
                    'items' => self::UUID_SCHEMA,
                ],
            ],
        ];
    }
}
