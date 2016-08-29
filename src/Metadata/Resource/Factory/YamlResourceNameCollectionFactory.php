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
use Symfony\Component\Yaml\Parser as YamlParser;

/**
 * Creates a resource name collection from {@see Resource} configuration files.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class YamlResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    private $yamlParser;
    private $paths;
    private $decorated;

    /**
     * @param string[]                                    $paths
     * @param ResourceNameCollectionFactoryInterface|null $decorated
     */
    public function __construct(array $paths, ResourceNameCollectionFactoryInterface $decorated = null)
    {
        $this->yamlParser = new YamlParser();
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
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
            $resources = $this->yamlParser->parse(file_get_contents($path));

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
