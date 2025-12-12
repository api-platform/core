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

use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
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
class AbstractUuidFilter extends AbstractFilter implements OpenApiParameterFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;

    private const UUID_SCHEMA = [
        'type' => 'string',
        'format' => 'uuid',
    ];

    protected function filterProperty(string $property, mixed $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (
            null === $value
            || !$this->isPropertyEnabled($property, $resourceClass)
            || !$this->isPropertyMapped($property, $resourceClass, true)
        ) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $property;

        $values = $this->normalizeValues((array) $value, $property);
        if (null === $values) {
            return;
        }

        $associations = [];
        if ($this->isPropertyNested($property, $resourceClass)) {
            [$alias, $field, $associations] = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder, $queryNameGenerator, $resourceClass, Join::INNER_JOIN);
        }

        $metadata = $this->getNestedMetadata($resourceClass, $associations);

        if ($metadata->hasField($field)) {
            $values = $this->convertValuesToTheDatabaseRepresentationIfNecessary($queryBuilder, $this->getDoctrineFieldType($property, $resourceClass), $values);
            $this->addWhere($queryBuilder, $queryNameGenerator, $alias, $field, $values);

            return;
        }

        // metadata doesn't have the field, nor an association on the field
        if (!$metadata->hasAssociation($field)) {
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

        $values = $this->convertValuesToTheDatabaseRepresentationIfNecessary($queryBuilder, $doctrineTypeField, $values);
        $this->addWhere($queryBuilder, $queryNameGenerator, $associationAlias, $associationField, $values);
    }

    /**
     * Converts values to their database representation.
     */
    private function convertValuesToTheDatabaseRepresentationIfNecessary(QueryBuilder $queryBuilder, ?string $doctrineFieldType, array $values): array
    {
        if ($doctrineFieldType && Type::hasType($doctrineFieldType)) {
            $doctrineType = Type::getType($doctrineFieldType);
            $platform = $queryBuilder->getEntityManager()->getConnection()->getDatabasePlatform();
            $databaseValues = [];

            foreach ($values as $value) {
                try {
                    $databaseValues[] = $doctrineType->convertToDatabaseValue($value, $platform);
                } catch (ConversionException $e) {
                    $this->logger->notice('Invalid value conversion value to its database representation', [
                        'exception' => $e,
                    ]);
                    $databaseValues[] = null;
                }
            }

            return $databaseValues;
        }

        return $values;
    }

    /**
     * Adds where clause.
     */
    private function addWhere(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $field, mixed $values): void
    {
        if (!\is_array($values)) {
            $values = [$values];
        }

        $valueParameter = ':'.$queryNameGenerator->generateParameterName($field);
        $aliasedField = \sprintf('%s.%s', $alias, $field);

        if (1 === \count($values)) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq($aliasedField, $valueParameter))
                ->setParameter($valueParameter, $values[0], $this->getDoctrineParameterType());

            return;
        }

        $queryBuilder
            ->andWhere($queryBuilder->expr()->in($aliasedField, $valueParameter))
            ->setParameter($valueParameter, $values, $this->getDoctrineArrayParameterType());
    }

    protected function getDoctrineParameterType(): ?ParameterType
    {
        return null;
    }

    protected function getDoctrineArrayParameterType(): ?ArrayParameterType
    {
        return null;
    }

    public function getOpenApiParameters(Parameter $parameter): array
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';
        $key = $parameter->getKey();

        return [
            new OpenApiParameter(
                name: $key,
                in: $in,
                schema: self::UUID_SCHEMA,
                style: 'form',
                explode: false
            ),
            new OpenApiParameter(
                name: $key.'[]',
                in: $in,
                description: 'One or more Uuids',
                schema: [
                    'type' => 'array',
                    'items' => self::UUID_SCHEMA,
                ],
                style: 'deepObject',
                explode: true
            ),
        ];
    }

    /**
     * Normalize the values array.
     */
    protected function normalizeValues(array $values, string $property): ?array
    {
        foreach ($values as $key => $value) {
            if (!\is_string($value)) {
                unset($values[$key]);
            }
        }

        if (0 === \count($values)) {
            $this->getLogger()->notice('Invalid filter ignored', [
                'exception' => new InvalidArgumentException(\sprintf('At least one value is required, multiple values should be in "%1$s[]=019b3c90-e265-72e5-a594-17b446a4067f&%1$s[]=019b3c9b-bce6-76dc-a066-9a44f4ec253f" format', $property)),
            ]);

            return null;
        }

        return array_values($values);
    }
}
