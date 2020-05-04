<?php


namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Config\Resource\DirectoryResource;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Tebaly <admin@freedomsex.net>
 */
class DirectoryResourceAutoLoader extends Loader
{
    private $resourceClassDirectories;

    public function __construct(
        array $resourceClassDirectories = []
    ) {
        $this->resourceClassDirectories = $resourceClassDirectories;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resourceClass, $type = null): RouteCollection
    {
        $collection = new RouteCollection();
        foreach ($this->resourceClassDirectories as $directory) {
            $collection->addResource(new DirectoryResource($directory, '/\.php$/'));
        }
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_directory_autoload' === $type;
    }
}
