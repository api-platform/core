<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Filter by a numeric value.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class NumericFilter extends AbstractFilter
{
    /**
     * Type of numeric in Doctrine.
     *
     * @see http://doctrine-orm.readthedocs.org/projects/doctrine-dbal/en/latest/reference/types.html
     */
    const DOCTRINE_NUMERIC_TYPES = [
        DBALType::BIGINT => true,
        DBALType::DECIMAL => true,
        DBALType::FLOAT => true,
        DBALType::INTEGER => true,
        DBALType::SMALLINT => true,
    ];

    /*
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, array $properties = null)
    {
        parent::__construct($managerRegistry, $properties);

        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     *
     * Check whether a value is a numerical and equal to the request value.
     *
     * For each property passed, if the resource does not have such property or if the  value is not numerical the property is ignored.
     */
    public function apply(QueryBuilder $queryBuilder, string $resourceClass, string $operationName = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $properties = $this->extractProperties($request);

        foreach ($properties as $property => $numerical) {
            if (!$this->isPropertyEnabled($property) || !$this->isPropertyMapped($property, $resourceClass)) {
                continue;
            }

            if (empty($numerical) && isset($this->properties[$property])) {
                $numerical = $this->properties[$property];
            }
            if (!is_numeric($numerical) && !empty($numerical)) {
                continue;
            }

            $alias = 'o';
            $field = $property;

            if ($this->isPropertyNested($property)) {
                $propertyParts = $this->splitPropertyParts($property);

                $parentAlias = $alias;

                foreach ($propertyParts['associations'] as $association) {
                    $alias = QueryNameGenerator::generateJoinAlias($association);
                    $queryBuilder->leftJoin(sprintf('%s.%s', $parentAlias, $association), $alias);
                    $parentAlias = $alias;
                }

                $field = $propertyParts['field'];
            }
            $valueParameter = QueryNameGenerator::generateParameterName($field);

            $queryBuilder
                ->andWhere(sprintf('%s.%s = :%s', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $numerical);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass) : array
    {
        $description = [];

        $properties = $this->properties;
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resourceClass)->getFieldNames(), null);
        }

        foreach ($properties as $property => $numerical) {
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isNumericField($property, $resourceClass)) {
                continue;
            }
            $propertyParts = $this->splitPropertyParts($property);
            $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

            $description[$property] = [
                'property' => $property,
                'type' => $metadata->getTypeOfField($propertyParts['field']),
                'required' => false,
            ];
        }

        return $description;
    }

    /**
     * Determines whether the given property is a numeric or not.
     *
     * @param string $property
     * @param string $resourceClass
     *
     * @return bool
     */
    private function isNumericField(string $property, string $resourceClass) : bool
    {
        $propertyParts = $this->splitPropertyParts($property);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        return isset(self::DOCTRINE_NUMERIC_TYPES[$metadata->getTypeOfField($propertyParts['field'])]);
    }
}
