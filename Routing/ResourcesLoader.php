<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Routing;

use Dunglas\JsonLdApiBundle\Resources;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads {@see Dunglas\JsonLdApiBundle\Resources}.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourcesLoader extends Loader
{
    /**
     * @var Resources
     */
    private $resources;

    public function __construct(Resources $resources)
    {
        $this->resources = $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function load($data, $type = null)
    {
        $routeCollection = new RouteCollection();

        /**
         * @var $resource \Dunglas\JsonLdApiBundle\Resource
         */
        foreach ($this->resources as $resource)
        {
            $routeCollection->addCollection($resource->getRouteCollection());
        }

        return $routeCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'json-ld' === $type;
    }
}
