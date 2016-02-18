<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Doctrine\Orm\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Order the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class OrderFilter extends AbstractFilter
{
    /**
     * @var string Keyword used to retrieve the value.
     */
    private $orderParameterName;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, string $orderParameterName, array $properties = null)
    {
        parent::__construct($managerRegistry, $properties);

        $this->orderParameterName = $orderParameterName;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     *
     * Orders collection by properties. The order of the ordered properties is the same as the order specified in the
     * query.
     * For each property passed, if the resource does not have such property or if the order value is different from
     * `asc` or `desc` (case insensitive), the property is ignored.
     */
    public function apply(QueryBuilder $queryBuilder, string $resourceClass, string $operationName = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $properties = $this->extractProperties($request);

        foreach ($properties as $property => $order) {
            if (!$this->isPropertyEnabled($property) || !$this->isPropertyMapped($property, $resourceClass)) {
                continue;
            }

            if (empty($order) && isset($this->properties[$property])) {
                $order = $this->properties[$property];
            }

            $order = strtoupper($order);
            if (!in_array($order, ['ASC', 'DESC'])) {
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

            $queryBuilder->addOrderBy(sprintf('%s.%s', $alias, $field), $order);
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

        foreach ($properties as $property => $order) {
            if (!$this->isPropertyMapped($property, $resourceClass)) {
                continue;
            }

            $description[sprintf('%s[%s]', $this->orderParameterName, $property)] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
            ];
        }

        return $description;
    }

    /**
     * {@inheritdoc}
     */
    protected function extractProperties(Request $request) : array
    {
        return $request->query->get($this->orderParameterName, []);
    }
}
