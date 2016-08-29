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

/**
 * Creates a resource name collection from {@see Resource} configuration files.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class XmlResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    private $xmlParser;
    private $paths;
    private $decorated;

    const RESOURCE_SCHEMA = __DIR__.'/../../../schema/metadata.xsd';

    /**
     * @param string[]                                    $paths
     * @param ResourceNameCollectionFactoryInterface|null $decorated
     */
    public function __construct(array $paths, ResourceNameCollectionFactoryInterface $decorated = null)
    {
        $this->xmlParser = new \DOMDocument();
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
            $resources = $this->getResourcesDom($path);

            $internalErrors = libxml_use_internal_errors(true);

            if (false === @$resources->schemaValidate(self::RESOURCE_SCHEMA)) {
                throw new InvalidArgumentException(sprintf('XML Schema loaded from path %s is not valid! Errors: %s', realpath($path), implode("\n", $this->getXmlErrors($internalErrors))));
            }

            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            foreach ($resources->getElementsByTagName('resource') as $resource) {
                $classes[$resource->getAttribute('class')] = true;
            }
        }

        return new ResourceNameCollection(array_keys($classes));
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
