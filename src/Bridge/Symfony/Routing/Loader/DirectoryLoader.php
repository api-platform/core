<?php


namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;


use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Routing\RouteCollection;

class DirectoryLoader extends FileLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);
        $collection = new RouteCollection();
        $collection->addResource(new DirectoryResource($path, '/\.php$/'));
        foreach (scandir($path) as $dir) {
            if ('.' !== $dir[0]) {
                $directory = $path.'/'.$dir;
                if (is_dir($directory)) {
                    $subCollection = $this->import($directory, 'api_directory');
                    $collection->addCollection($subCollection);
                }
            }
        }
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
