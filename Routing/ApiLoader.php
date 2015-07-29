<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Routing;

use Dunglas\ApiBundle\Api\ResourceCollectionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Loader\XmlFileLoader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiLoader extends Loader
{
    const ROUTE_NAME_PREFIX = 'api_';

    /**
     * @var ResourceCollectionInterface
     */
    private $resourceCollection;
    /**
     * @var XmlFileLoader
     */
    private $fileLoader;

    public function __construct(ResourceCollectionInterface $resourceCollection, KernelInterface $kernel)
    {
        $this->resourceCollection = $resourceCollection;
        $this->fileLoader = new XmlFileLoader(new FileLocator($kernel->locateResource('@DunglasApiBundle/Resources/config/routing')));
    }

    /**
     * {@inheritdoc}
     */
    public function load($data, $type = null)
    {
        $routeCollection = new RouteCollection();

        $routeCollection->addCollection($this->fileLoader->load('jsonld.xml'));
        $routeCollection->addCollection($this->fileLoader->load('hydra.xml'));

        foreach ($this->resourceCollection as $resource) {
            foreach ($resource->getCollectionOperations() as $operation) {
                $routeCollection->add($operation->getRouteName(), $operation->getRoute());
            }

            foreach ($resource->getItemOperations() as $operation) {
                $routeCollection->add($operation->getRouteName(), $operation->getRoute());
            }
        }

        return $routeCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api' === $type;
    }
}
