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

/**
 * Creates a resource metadata from xml {@see Resource} configuration.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class XmlResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $xmlParser;
    private $paths;
    private $decorated;

    const RESOURCE_SCHEMA = __DIR__.'/../../../schema/metadata.xsd';

    /**
     * @param string[]                              $paths
     * @param ResourceMetadataFactoryInterface|null $decorated
     */
    public function __construct(array $paths, ResourceMetadataFactoryInterface $decorated = null)
    {
        $this->xmlParser = new \DOMDocument();
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
            $resources = $this->getResourcesDom($path);

            $internalErrors = libxml_use_internal_errors(true);

            if (false === @$resources->schemaValidate(self::RESOURCE_SCHEMA)) {
                throw new InvalidArgumentException(sprintf('XML Schema loaded from path %s is not valid! Errors: %s', realpath($path), implode("\n", $this->getXmlErrors($internalErrors))));
            }

            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            foreach ($resources->getElementsByTagName('resource') as $resource) {
                $class = $resource->getAttribute('class');

                if ($resourceClass !== $class) {
                    continue;
                }

                $metadata = $resource;

                break 2;
            }
        }

        if (null === $metadata) {
            return $this->handleNotFound($parentResourceMetadata, $resourceClass);
        }

        $xpath = new \DOMXpath($resources);

        $metadata = [
          'shortName' => $metadata->getAttribute('shortName') ?: null,
          'description' => $metadata->getAttribute('description') ?: null,
          'iri' => $metadata->getAttribute('iri') ?: null,
          'type' => $metadata->getAttribute('type') ?: null,
          'itemOperations' => $this->getOperations($xpath->query('./itemOperations/operation', $metadata)) ?: null,
          'collectionOperations' => $this->getOperations($xpath->query('./collectionOperations/operation', $metadata)) ?: null,
          'attributes' => $this->getAttributes($xpath->query('./attributes/attribute', $metadata)),
        ];

        if (!$parentResourceMetadata) {
            return new ResourceMetadata(
              $metadata['shortName'],
              $metadata['description'],
              $metadata['iri'],
              $metadata['type'],
              $metadata['itemOperations'],
              $metadata['collectionOperations'],
              $metadata['attributes']
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
     * Creates a DOMDocument based on `resource` tags of a file-loaded xml document.
     *
     * @param string $path the xml file path
     *
     * @return \DOMDocument
     */
    private function getResourcesDom(string $path) : \DOMDocument
    {
        $doc = new \DOMDocument('1.0', 'utf-8');
        $root = $doc->createElement('resources');
        $doc->appendChild($root);

        $this->xmlParser->loadXML(file_get_contents($path));

        $xpath = new \DOMXpath($this->xmlParser);
        $resources = $xpath->query('//resource');

        foreach ($resources as $resource) {
            $root->appendChild($doc->importNode($resource, true));
        }

        return $doc;
    }

    /**
     * Get operations from xml.
     *
     * @param \DOMNodeList $query
     *
     * @return array|null
     */
    private function getOperations(\DOMNodeList $query)
    {
        $operations = [];
        foreach ($query as $operation) {
            $key = $operation->getAttribute('key');
            $operations[$key] = [
          'method' => $operation->getAttribute('method'),
        ];

            $path = $operation->getAttribute('path');

            if ($path) {
                $operations[$key]['path'] = $path;
            }
        }

        return $operations ?: null;
    }

    /**
     * Get Attributes.
     *
     * @param \DOMNodeList $query
     *
     * @return array|null
     */
    private function getAttributes(\DOMNodeList $query)
    {
        $attributes = [];
        foreach ($query as $attribute) {
            $key = $attribute->getAttribute('key');
            $attributes[$key] = $this->recursiveAttributes($attribute, $attributes[$key]);
        }

        return $attributes ?: null;
    }

    /**
     * Transforms random attributes in an array
     * <element (key="key"|int)>\DOMNodeList|\DOMText</element>.
     *
     * @param \DOMElement $element
     * @param array
     *
     * @return array|string
     */
    private function recursiveAttributes(\DOMElement $element, &$attributes)
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMText) {
                if ($child->isWhitespaceInElementContent()) {
                    continue;
                }

                $attributes = $child->nodeValue;
                break;
            }

            $key = $child->getAttribute('key') ?: count($attributes);
            $attributes[$key] = $child->childNodes->length ? $this->recursiveAttributes($child, $attributes[$key]) : $child->value;
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

    /**
     * Returns the XML errors of the internal XML parser.
     *
     * @param bool $internalErrors
     *
     * @return array An array of errors
     */
    private function getXmlErrors($internalErrors)
    {
        $errors = [];
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf('[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ?: 'n/a',
                $error->line,
                $error->column
            );
        }
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $errors;
    }
}
