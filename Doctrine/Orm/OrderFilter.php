<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class OrderFilter extends AbstractFilter
{
    /**
     * @var array List of properties by witch the collection can or cannot be ordered.
     */
    private $properties;

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
        $this->managerRegistry = $managerRegistry;
        $this->orderParameter  = $orderParameter;
        $this->properties      = $properties;
    }

    /**
     * {@inheritdoc}
     *
     * Order collection by properties. The order of the ordered properties is the same as the order specified in the
     * query.
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request)
    {
        $this->applyFilter($resource, $queryBuilder, $request->query->get($this->orderParameter));
    }

    /**
     * Create query to order the collection by the properties passed.
     * For each property passed, if the resource does not have such property or if the order value is different from
     * `asc` or `desc` (case insensitive), the property is ignored.
     *
     * @param ResourceInterface $resource
     * @param QueryBuilder      $queryBuilder
     * @param array|null        $values Array of properties as key and order value as value.
     *
     * @return QueryBuilder
     */
    protected function applyFilter(ResourceInterface $resource, QueryBuilder $queryBuilder, array $values = null)
    {
        $fieldNames = $this->getClassMetadata($resource)->getFieldNames();

        if (null !== $values) {
            foreach ($values as $property => $order) {
                $order = strtoupper($order);

                // Check if property is enabled or if filter is not enabled on all properties
                if (null !== $this->properties) {
                    if (false === in_array($property, $this->properties)) {
                        continue;   // Skip this property
                    }
                }

                if (true === in_array($property, $fieldNames)   // Check if the entity has the property
                    && ('ASC' === $order || 'DESC' === $order)  // Check if order value is valid
                ) {
                    $queryBuilder->addOrderBy(sprintf('o.%s', $property), $order);
                }
            }
        }

        return $queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(ResourceInterface $resource)
    {
        $description = [];
        $metadata = $this->getClassMetadata($resource);

        foreach ($metadata->getFieldNames() as $fieldName) {
            $found = in_array($fieldName, $this->properties);
            if ($found || null === $this->properties) {
                $description[sprintf('%s[%s]', $this->orderParameter, $fieldName)] = [
                    'property' => $fieldName,
                    'type'     => 'string',
                    'required' => false,
                ];
            }
        }

        return $description;
    }
}
