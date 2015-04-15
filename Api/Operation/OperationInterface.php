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
 * Operation doable on a Resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface OperationInterface
{
    /**
     * Gets route.
     *
     * @return Route
     */
    public function getRoute();

    /**
     * Gets route name.
     *
     * @return string
     */
    public function getRouteName();

    /**
     * Gets context.
     *
     * @return array
     */
    public function getContext();
}
