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

use Doctrine\Common\Inflector\Inflector;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\Routing\Route;

/**
 * Creates operations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class OperationFactory
{
    const ROUTE_NAME_PREFIX = 'api_';
    const DEFAULT_ACTION_PATTERN = 'api.action.';

    /**
     * @var array
     */
    private static $inflectorCache = [];

    /**
     * Creates collection operation.
     *
     * @param ResourceInterface $resource
     * @param string|array      $methods
     * @param string|null       $path
     * @param null              $controller
     * @param null              $routeName
     * @param array             $context
     *
     * @return Operation
     */
    public function createCollectionOperation(
        ResourceInterface $resource,
        $methods,
        $path = null,
        $controller = null,
        $routeName = null,
        array $context = []
    ) {
        return $this->createOperation($resource, true, $methods, $path, $controller, $routeName, $context);
    }

    /**
     * Creates item operation.
     *
     * @param ResourceInterface $resource
     * @param string|array      $methods
     * @param string|null       $path
     * @param null              $controller
     * @param null              $routeName
     * @param array             $context
     *
     * @return Operation
     */
    public function createItemOperation(
        ResourceInterface $resource,
        $methods,
        $path = null,
        $controller = null,
        $routeName = null,
        array $context = []
    ) {
        return $this->createOperation($resource, false, $methods, $path, $controller, $routeName, $context);
    }

    /**
     * Creates operation.
     *
     * @param ResourceInterface $resource
     * @param bool              $collection
     * @param string|array      $methods
     * @param string|null       $path
     * @param string|null       $controller
     * @param string|null       $routeName
     * @param array             $context
     *
     * @return Operation
     */
    private function createOperation(
        ResourceInterface $resource,
        $collection,
        $methods,
        $path = null,
        $controller = null,
        $routeName = null,
        array $context = []
    ) {
        $shortName = $resource->getShortName();

        if (!isset(self::$inflectorCache[$shortName])) {
            self::$inflectorCache[$shortName] = Inflector::pluralize(Inflector::tableize($shortName));
        }

        // Populate path
        if (null === $path) {
            $path = '/'.self::$inflectorCache[$shortName];

            if (!$collection) {
                $path .= '/{id}';
            }
        }

        // Guess default method
        if (is_array($methods)) {
            $defaultMethod = $methods[0];
        } else {
            $defaultMethod = $methods;
        }

        // Populate controller
        if (null === $controller) {
            $actionName = sprintf('%s_%s', strtolower($defaultMethod), $collection ? 'collection' : 'item');

            $controller = self::DEFAULT_ACTION_PATTERN.$actionName;

            // Populate route name
            if (null === $routeName) {
                $routeName = sprintf('%s%s_%s', self::ROUTE_NAME_PREFIX, self::$inflectorCache[$shortName], $actionName);
            }
        }

        return new Operation(
            new Route(
                $path,
                [
                    '_controller' => $controller,
                    '_resource' => $shortName,
                ],
                [],
                [],
                '',
                [],
                $methods
            ),
            $routeName,
            $context
        );
    }
}
