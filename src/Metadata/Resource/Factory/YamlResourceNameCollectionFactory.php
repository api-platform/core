<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Creates a resource name collection from {@see Resource} configuration files.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class YamlResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    private $paths;
    private $decorated;

    /**
     * @param string[]                                    $paths
     * @param ResourceNameCollectionFactoryInterface|null $decorated
     */
    public function __construct(array $paths, ResourceNameCollectionFactoryInterface $decorated = null)
    {
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ParseException
     * @throws InvalidArgumentException
     */
    public function create() : ResourceNameCollection
    {
        $classes = [];
        if ($this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        foreach ($this->paths as $path) {
            try {
                $resources = Yaml::parse(file_get_contents($path));
            } catch (ParseException $parseException) {
                $parseException->setParsedFile($path);

                throw $parseException;
            }

            $resources = $resources['resources'] ?? $resources;

            foreach ($resources as $resource) {
                if (!isset($resource['class'])) {
                    throw new InvalidArgumentException('Resource must represent a class, none found!');
                }

                $classes[$resource['class']] = true;
            }
        }

        return new ResourceNameCollection(array_keys($classes));
    }
}
