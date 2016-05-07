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
 * Filter by a boolean value.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class BooleanFilter extends AbstractFilter
{
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
     * Check whether a value is equal to 1, 0, true, false, on, off (case insensitive)
     *
     * For each property passed, if the resource does not have such property or if the order value is different from
     * 1, 0, true, false, on, off (case insensitive) the property is ignored.
     */
    public function apply(QueryBuilder $queryBuilder, string $resourceClass, string $operationName = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $properties = $this->extractProperties($request);

        foreach ($properties as $property => $boolean) {
            if (!$this->isPropertyEnabled($property) || !$this->isPropertyMapped($property, $resourceClass)) {
                continue;
            }

            if (empty($boolean) && isset($this->properties[$property])) {
                $boolean = $this->properties[$property];
            }

            $filterBoolean = filter_var($boolean, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if (is_null($filterBoolean) && !empty($boolean)) {
                continue;
            }

            $alias = 'o';
            $field = $property;

            if ($this->isPropertyNested($property)) {
                list($alias, $field) = $this->addJoinsForNestedProperty($property, $alias, $queryBuilder);
            }
            $valueParameter = QueryNameGenerator::generateParameterName($field);

            $queryBuilder
                ->andWhere(sprintf('%s.%s = :%s', $alias, $field, $valueParameter))
                ->setParameter($valueParameter, $filterBoolean);
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

        foreach ($properties as $property => $boolean) {
            if (!$this->isPropertyMapped($property, $resourceClass) || !$this->isBooleanField($property, $resourceClass)) {
                continue;
            }

            $description[$property] = [
                'property' => $property,
                'type' => 'boolean',
                'required' => false,
            ];
        }

        return $description;
    }

    /**
     * Determines whether the given property is a boolean or not.
     *
     * @param string $property
     * @param string $resourceClass
     *
     * @return bool
     */
    private function isBooleanField(string $property, string $resourceClass) : bool
    {
        $propertyParts = $this->splitPropertyParts($property);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        return DBALType::BOOLEAN === $metadata->getTypeOfField($propertyParts['field']);
    }
}
