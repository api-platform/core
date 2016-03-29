<?php

/*
 * This file is part of the API Platform project.
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
    const DEFAULT_CONTROLLER = 'DunglasApiBundle:Resource';

    /**
     * @var array
     */
    private static $inflectorCache = [];

    /**
     * @var string
     */
    private $defaultController = self::DEFAULT_CONTROLLER;

    /**
     * Sets the name of the default controller to use.
     *
     * @param string $defaultController
     */
    public function setDefaultController($defaultController)
    {
        $this->defaultController = $defaultController;
    }

    /**
     * Creates collection operation.
     *
     * @param ResourceInterface $resource
     * @param string|array      $methods
     * @param string|null       $path
     * @param null              $controller
     * @param null              $routeName
     * @param array             $context
     * @param array             $requirements
     *
     * @return Operation
     */
    public function createCollectionOperation(
        ResourceInterface $resource,
        $methods,
        $path = null,
        $controller = null,
        $routeName = null,
        array $context = [],
        $requirements = []
    ) {
        return $this->createOperation($resource, true, $methods, $path, $controller, $routeName, $context, $requirements);
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
     * @param array             $requirements
     *
     * @return Operation
     */
    public function createItemOperation(
        ResourceInterface $resource,
        $methods,
        $path = null,
        $controller = null,
        $routeName = null,
        array $context = [],
        $requirements = []
    ) {
        return $this->createOperation($resource, false, $methods, $path, $controller, $routeName, $context, $requirements);
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
     * @param array             $requirements
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
        array $context = [],
        $requirements = []
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
            $defaultAction = strtolower($defaultMethod);

            if ($collection) {
                $defaultAction = 'c'.$defaultAction;
            }

            $controller = $this->defaultController.':'.$defaultAction;

            // Populate route name
            if (null === $routeName) {
                $routeName = self::ROUTE_NAME_PREFIX.self::$inflectorCache[$shortName].'_'.$defaultAction;
            }
        }

        return new Operation(
            new Route(
                $path,
                [
                    '_controller' => $controller,
                    '_resource' => $shortName,
                ],
                $requirements,
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
