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
class DateFilter extends AbstractFilter
{
    /**
     * @var array List of properties by witch the collection can or cannot be ordered.
     */
    private $properties;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param array|null      $properties      List of property names on which the filter will be enabled.
     */
    public function __construct(ManagerRegistry $managerRegistry, array $properties = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     *
     * Order collection by properties. The order of the ordered properties is the same as the order specified in the
     * query.
     */
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder, Request $request)
    {
        $metadata = $this->getClassMetadata($resource);
        $fieldNames = $metadata->getFieldNames();

        foreach ($request->query->all() as $filter => $values) {
            if (!is_array($values)) {
                continue;
            }

            if (null !== $this->properties) {
                if (false === in_array($filter, $this->properties)) {
                    continue;
                }
            }

            if (in_array($filter, $fieldNames)) {
                foreach ($values as $period => $date) {
                    $period = strtolower($period);
                    $date = new \DateTime($date);

                    if ('before' === $period) {
                        $parameter = sprintf('%s%s', $period, $filter);
                        $queryBuilder
                            ->andWhere(sprintf('o.%s <= :%s', $filter, $parameter))
                            ->setParameter($parameter, $date)
                        ;
                    }

                    if ('after' === $period) {
                        $parameter = sprintf('%s%s', $period, $filter);
                        $queryBuilder
                            ->andWhere(sprintf('o.%s >= :%s', $filter, $parameter))
                            ->setParameter($parameter, $date)
                        ;
                    }
                }
            }
        }
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
                $description['string'] = [
                    'property' => $fieldName,
                    'type' => 'string',
                    'required' => false,
                ];
            }
        }

        return $description;
    }
}
