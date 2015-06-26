<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Extension;

use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Doctrine\Orm\QueryExtensionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Applies selected ordering while querying resource collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class OrderExtension implements QueryExtensionInterface
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
