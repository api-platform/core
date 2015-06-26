<?php

namespace Dunglas\ApiBundle\Doctrine\Orm\Extension;

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\QueryExtension;
use Symfony\Component\HttpFoundation\Request;

class OrderExtension implements QueryExtension
{
    /**
     * @var string|null
     */
    private $order;

    /**
     * @param string|null $order
     */
    public function __construct($order = null)
    {
        $this->order = $order;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ResourceInterface $resource, Request $request, QueryBuilder $queryBuilder)
    {
        if (null !== $this->order) {
            $queryBuilder->addOrderBy('o.id', $this->order);
        }
    }
}
