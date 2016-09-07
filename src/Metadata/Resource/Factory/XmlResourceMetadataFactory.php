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
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Creates a resource metadata from XML {@see Resource} configuration.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class XmlResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    const RESOURCE_SCHEMA = __DIR__.'/../../schema/metadata.xsd';

    private $paths;
    private $decorated;

    /**
     * @param string[]                              $paths
     * @param ResourceMetadataFactoryInterface|null $decorated
     */
    public function __construct(array $paths, ResourceMetadataFactoryInterface $decorated = null)
    {
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

        if (!class_exists($resourceClass) || empty($metadata = $this->getMetadata($resourceClass))) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        return null === $parentResourceMetadata ? new ResourceMetadata(...$metadata) : $this->update($parentResourceMetadata, $metadata);
    }

    /**
     * Extracts metadata from the XML tree.
     *
     * @param string $resourceClass
     *
     * @return array
     */
    private function getMetadata(string $resourceClass) : array
    {
        foreach ($this->paths as $path) {
            try {
                $domDocument = XmlUtils::loadFile($path, self::RESOURCE_SCHEMA);
            } catch (\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            $xml = simplexml_import_dom($domDocument);
            foreach ($xml->resource as $resource) {
                if ($resourceClass !== (string) $resource['class']) {
                    continue;
                }

                return [
                    (string) $resource['shortName'] ?? null,
                    (string) $resource['description'] ?? null,
                    (string) $resource['iri'] ?? null,
                    $this->getAttributes($resource, 'itemOperation') ?: null,
                    $this->getAttributes($resource, 'collectionOperation') ?: null,
                    $this->getAttributes($resource, 'attribute') ?: null,
                ];
            }
        }

        return [];
    }

    /**
     * Recursively transforms an attribute structure into an associative array.
     *
     * @param \SimpleXMLElement $resource
     * @param string            $elementName
     *
     * @return array
     */
    private function getAttributes(\SimpleXMLElement $resource, string $elementName) : array
    {
        $attributes = [];
        foreach ($resource->$elementName as $attribute) {
            $value = isset($attribute->attribute[0]) ? $this->getAttributes($attribute, 'attribute') : (string) $attribute;
            isset($attribute['name']) ? $attributes[(string) $attribute['name']] = $value : $attributes[] = $value;
        }

        return $attributes;
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
     * @param array            $metadata
     *
     * @return ResourceMetadata
     */
    private function update(ResourceMetadata $resourceMetadata, array $metadata) : ResourceMetadata
    {
        foreach (['shortName', 'description', 'iri', 'itemOperations', 'collectionOperations', 'attributes'] as $key => $property) {
            if (null === $metadata[$key] || null !== $resourceMetadata->{'get'.ucfirst($property)}()) {
                continue;
            }

            $resourceMetadata = $resourceMetadata->{'with'.ucfirst($property)}($metadata[$key]);
        }

        return $resourceMetadata;
    }
}
