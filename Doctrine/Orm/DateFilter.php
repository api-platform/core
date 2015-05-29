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
    const PARAMETER_BEFORE = 'before';
    const PARAMETER_AFTER = 'after';

    const NULL_EXCLUDED = 0;
    const NULL_FIRST = 1;
    const NULL_LAST = 2;

    /**
     * @var array List of properties by witch the collection can be ordered.
     */
    private $properties;

    /**
     * @var int Specify the NULL position when sorting dates
     */
    private $nullOption = self::NULL_FIRST;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param array|null      $properties      List of property names on which the filter will be enabled.
     * @param int             $nullOption
     */
    public function __construct(ManagerRegistry $managerRegistry, array $properties = null, $nullOption = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->properties = $properties;
        if (in_array($nullOption, [self::NULL_EXCLUDED, self::NULL_FIRST, self::NULL_LAST])) {
            $this->nullOption = $nullOption;
        }
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
        $fieldNames = array_flip($metadata->getFieldNames());

        foreach ($request->query->all() as $filter => $values) {
            // Expect $values to be an array having the period as keys and the date value as values
            if (!is_array($values)) {
                continue;
            }

            // Check if filter is in whitelist mode and if so if the filter is enabled
            if (null !== $this->properties && !in_array($filter, $this->properties)) {
                continue;
            }

            // Check if entity has such single association property
            if (isset($fieldNames[$filter])) {
                foreach ($values as $period => $date) {
                    $period = strtolower($period);
                    $date = new \DateTime($date);
                    $parameter = sprintf('date_%s_%s', $period, $filter);

                    switch ($this->nullOption) {

                        case self::NULL_EXCLUDED:
                            switch ($period) {

                                case self::PARAMETER_BEFORE:
                                    $expr = $queryBuilder->expr()->andX(
                                        sprintf('o.%s <= :%s', $filter, $parameter),
                                        $queryBuilder->expr()->isNotNull(sprintf('o.%s', $filter))
                                    );
                                    break;

                                case self::PARAMETER_AFTER:
                                    $queryBuilder->andWhere(sprintf('o.%s >= :%s', $filter, $parameter));
                            }
                            break;

                        case self::NULL_FIRST:
                            switch ($period) {

                                case self::PARAMETER_BEFORE:
                                    $queryBuilder->andWhere(sprintf('o.%s <= :%s', $filter, $parameter));
                                    break;

                                case self::PARAMETER_AFTER:
                                    $queryBuilder->andWhere(sprintf('o.%s >= :%s', $filter, $parameter));
                            }
                            break;

                        case self::NULL_LAST:
                            switch ($period) {

                                case self::PARAMETER_BEFORE:
                                    $expr = $queryBuilder->expr()->andX(
                                        sprintf('o.%s <= :%s', $filter, $parameter),
                                        $queryBuilder->expr()->isNotNull(sprintf('o.%s', $filter))
                                    );
                                    break;

                                case self::PARAMETER_AFTER:
                                    $expr = $queryBuilder->expr()->orX(
                                        sprintf('o.%s >= :%s', $filter, $parameter),
                                        $queryBuilder->expr()->isNull(sprintf('o.%s', $filter))
                                    );
                            }
                    }

                    if (isset($expr)) {
                        $queryBuilder
                            ->andWhere($expr)
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
                    'type'     => 'string',
                    'required' => false,
                ];
            }
        }

        return $description;
    }
}
