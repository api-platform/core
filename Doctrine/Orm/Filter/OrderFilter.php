<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\Util\QueryNameGenerator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Order the collection by given properties.
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class OrderFilter extends AbstractFilter
{
    /**
     * @var string Keyword used to retrieve the value.
     */
    private $orderParameter;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string          $orderParameter  Keyword used to retrieve the value.
     * @param array|null      $properties      List of property names on which the filter will be enabled.
     */
    public function __construct(ManagerRegistry $managerRegistry, $orderParameter, array $properties = null)
    {
        parent::__construct($managerRegistry, $properties);

        $this->orderParameter = $orderParameter;
    }

    /**
     * {@inheritdoc}
     *
     * Orders collection by properties. The order of the ordered properties is the same as the order specified in the
     * query.
     * For each property passed, if the resource does not have such property or if the order value is different from
     * `asc` or `desc` (case insensitive), the property is ignored.
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request)
    {
        $properties = $this->extractProperties($request);

        foreach ($properties as $property => $order) {
            if (!$this->isPropertyEnabled($property) || !$this->isPropertyMapped($property, $resource)) {
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
    public function getDescription(ResourceInterface $resource)
    {
        $description = [];

        $properties = $this->properties;
        if (null === $properties) {
            $properties = array_fill_keys($this->getClassMetadata($resource)->getFieldNames(), null);
        }

        foreach ($properties as $property => $order) {
            if (!$this->isPropertyMapped($property, $resource)) {
                continue;
            }

            $description[sprintf('%s[%s]', $this->orderParameter, $property)] = [
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
    protected function extractProperties(Request $request)
    {
        return $request->query->get($this->orderParameter, []);
    }
}
