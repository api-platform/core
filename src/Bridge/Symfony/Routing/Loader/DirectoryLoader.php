<?php


namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;


use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Routing\RouteCollection;

class DirectoryLoader extends Loader
{
    /**
     * {@inheritdoc}
     */
    public function load($path, $type = null)
    {
        $collection = new RouteCollection();
        $collection->addResource(new DirectoryResource($path, '/\.php$/'));
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_directory' === $type;
    }
}
