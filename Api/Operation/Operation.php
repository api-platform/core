<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Api\Operation;

use Symfony\Component\Routing\Route;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Operation implements OperationInterface
{
    /**
     * @var Route
     */
    private $route;
    /**
     * @var string
     */
    private $routeName;
    /**
     * @var array
     */
    private $context;

    /**
     * @param Route  $route
     * @param string $routeName
     * @param array  $context
     */
    public function __construct(Route $route, $routeName, array $context = [])
    {
        $this->route = $route;
        $this->routeName = $routeName;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }
}
