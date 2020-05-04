<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Tebaly <admin@freedomsex.net>
 */
final class ApiLoader extends Loader
{
    /**
     * {@inheritdoc}
     */
    public function load($data, $type = null): RouteCollection
    {
        $collection = new RouteCollection();

        $collection->addCollection($this->import('.', 'api_external_autoload'));
        $collection->addCollection($this->import('.', 'api_directory_autoload'));
        $collection->addCollection($this->import('.', 'api_resource_autoload'));

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_platform' === $type;
    }
}
