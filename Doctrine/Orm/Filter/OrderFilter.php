<?php

/*
 * This file is part of the DunglasApiBundle package.
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Order the collection by given properties.
 *
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class OrderFilter extends AbstractFilter
{
    /**
     * @var string Keyword used to retrieve the value.
     */
    private $orderParameter;
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param RequestStack    $requestStack
     * @param string          $orderParameter  Keyword used to retrieve the value.
     * @param array|null      $properties      List of property names on which the filter will be enabled.
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        RequestStack $requestStack,
        $orderParameter,
        array $properties = null
    ) {
        parent::__construct($managerRegistry, $properties);

        $this->orderParameter = $orderParameter;
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
    public function apply(ResourceInterface $resource, QueryBuilder $queryBuilder)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        $properties = $this->extractProperties($request);
        $fieldNames = array_flip($this->getClassMetadata($resource)->getFieldNames());

        foreach ($properties as $property => $order) {
            if (!$this->isPropertyEnabled($property) || !isset($fieldNames[$property])) {
                continue;
            } elseif ('' === $order && isset($this->properties[$property])) {
                $order = $this->properties[$property];
            }

            $order = strtoupper($order);
            if ('ASC' === $order || 'DESC' === $order) {
                $queryBuilder->addOrderBy(sprintf('o.%s', $property), $order);
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
            if ($this->isPropertyEnabled($fieldName)) {
                $description[sprintf('%s[%s]', $this->orderParameter, $fieldName)] = [
                    'property' => $fieldName,
                    'type' => 'string',
                    'required' => false,
                ];
            }
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
