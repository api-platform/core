<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Creates a property name collection from XML {@see Property} configuration files.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class XmlPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    const RESOURCE_SCHEMA = __DIR__.'/../../schema/metadata.xsd';

    private $paths;
    private $decorated;

    /**
     * @param array                                       $paths
     * @param PropertyNameCollectionFactoryInterface|null $decorated
     */
    public function __construct(array $paths, PropertyNameCollectionFactoryInterface $decorated = null)
    {
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function create(string $resourceClass, array $options = []) : PropertyNameCollection
    {
        if ($this->decorated) {
            try {
                $propertyNameCollection = $this->decorated->create($resourceClass, $options);
            } catch (ResourceClassNotFoundException $resourceClassNotFoundException) {
                // Ignore not found exceptions from parent
            }
        }

        if (!class_exists($resourceClass)) {
            if (isset($propertyNameCollection)) {
                return $propertyNameCollection;
            }

            throw new ResourceClassNotFoundException(sprintf('The resource class "%s" does not exist.', $resourceClass));
        }

        $propertyNames = [];

        foreach ($this->paths as $path) {
            try {
                $domDocument = XmlUtils::loadFile($path, self::RESOURCE_SCHEMA);
            } catch (\InvalidArgumentException $e) {
                throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
            }

            $properties = (new \DOMXPath($domDocument))->query(sprintf('//resources/resource[@class="%s"]/property', $resourceClass));

            if (false === $properties || 0 >= $properties->length) {
                continue;
            }

            foreach ($properties as $property) {
                if ('' === $propertyName = $property->getAttribute('name')) {
                    continue;
                }

                $propertyNames[$propertyName] = true;
            }
        }

        if (isset($propertyNameCollection)) {
            foreach ($propertyNameCollection as $propertyName) {
                $propertyNames[$propertyName] = true;
            }
        }

        return new PropertyNameCollection(array_keys($propertyNames));
    }
}
