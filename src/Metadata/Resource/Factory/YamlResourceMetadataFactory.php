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
use ApiPlatform\Core\Metadata\YamlExtractor;

/**
 * Creates a resource metadata from yml {@see Resource} configuration.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class YamlResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $extractor;
    private $decorated;

    /**
     * @param YamlExtractor                         $extractor
     * @param ResourceMetadataFactoryInterface|null $decorated
     */
    public function __construct(YamlExtractor $extractor, ResourceMetadataFactoryInterface $decorated = null)
    {
        $this->extractor = $extractor;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function create(string $resourceClass): ResourceMetadata
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

        $resources = $this->extractor->getResources();
        if (!isset($resources[$resourceClass])) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        if (null === $parentResourceMetadata) {
            return new ResourceMetadata(
                $resources[$resourceClass]['shortName'],
                $resources[$resourceClass]['description'],
                $resources[$resourceClass]['iri'],
                $resources[$resourceClass]['itemOperations'],
                $resources[$resourceClass]['collectionOperations'],
                $resources[$resourceClass]['attributes']
            );
        }

        $resourceMetadata = $parentResourceMetadata;
        foreach (['shortName', 'description', 'itemOperations', 'collectionOperations', 'iri', 'attributes'] as $property) {
            if (!isset($resources[$resourceClass][$property])) {
                continue;
            }

            $resourceMetadata = $this->createWith($resourceMetadata, $property, $resources[$resourceClass][$property]);
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
    private function handleNotFound(ResourceMetadata $parentPropertyMetadata = null, string $resourceClass): ResourceMetadata
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
    private function createWith(ResourceMetadata $resourceMetadata, string $property, $value): ResourceMetadata
    {
        $getter = 'get'.ucfirst($property);

        if (null !== $resourceMetadata->$getter()) {
            return $resourceMetadata;
        }

        $wither = 'with'.ucfirst($property);

        return $resourceMetadata->$wither($value);
    }
}
