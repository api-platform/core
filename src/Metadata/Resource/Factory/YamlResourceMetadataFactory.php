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
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\Yaml\Parser as YamlParser;

/**
 * Creates a resource metadata from yml {@see Resource} configuration.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class YamlResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $yamlParser;
    private $decorated;
    private $paths;

    /**
     * @param string[]                              $paths
     * @param ResourceMetadataFactoryInterface|null $decorated
     */
    public function __construct(array $paths, ResourceMetadataFactoryInterface $decorated = null)
    {
        $this->yamlParser = new YamlParser();
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass) : ResourceMetadata
    {
        $parentResourceMetadata = null;
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        try {
            new \ReflectionClass($resourceClass);
        } catch (\ReflectionException $reflectionException) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        $metadata = null;

        foreach ($this->paths as $path) {
            $resources = $this->yamlParser->parse(file_get_contents($path));

            $resources = $resources['resources'] ?? $resources;

            foreach ($resources as $resource) {
                if (!isset($resource['class'])) {
                    throw new InvalidArgumentException('Resource must represent a class, none found!');
                }

                if ($resourceClass !== $resource['class']) {
                    continue;
                }

                $metadata = $resource;
                break 2;
            }
        }

        if (!$metadata || count($metadata) === 0) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        if (!$parentResourceMetadata) {
            return new ResourceMetadata(
                $metadata['shortName'] ?? null,
                $metadata['description'] ?? null,
                $metadata['iri'] ?? null,
                $metadata['type'] ?? null,
                $metadata['itemOperations'] ?? null,
                $metadata['collectionOperations'] ?? null,
                $metadata['attributes'] ?? null
            );
        }

        $resourceMetadata = $parentResourceMetadata;
        foreach (['shortName', 'description', 'itemOperations', 'collectionOperations', 'iri', 'type', 'attributes'] as $property) {
            if (!isset($metadata[$property])) {
                continue;
            }

            $resourceMetadata = $this->createWith($resourceMetadata, $property, $metadata[$property]);
        }

        return $resourceMetadata;
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param ResourceMetadata|null $parentPropertyMetadata
     * @param string                $resourceClass
     *
     * @throws ResourceClassNotFoundException
     *
     * @return ResourceMetadata
     */
    private function handleNotFound(ResourceMetadata $parentPropertyMetadata = null, string $resourceClass) : ResourceMetadata
    {
        if (null !== $parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new ResourceClassNotFoundException(sprintf('Resource "%s" not found.', $resourceClass));
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     *
     * @param ResourceMetadata $resourceMetadata
     * @param string           $property
     * @param mixed            $value
     *
     * @return ResourceMetadata
     */
    private function createWith(ResourceMetadata $resourceMetadata, string $property, $value) : ResourceMetadata
    {
        $getter = 'get'.ucfirst($property);

        if (null !== $resourceMetadata->$getter()) {
            return $resourceMetadata;
        }

        $wither = 'with'.ucfirst($property);

        return $resourceMetadata->$wither($value);
    }
}
