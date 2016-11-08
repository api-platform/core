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
use ApiPlatform\Core\Metadata\YamlExtractor;

/**
 * Creates a property name collection from YAML {@see Property} configuration files.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class YamlPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    private $extractor;
    private $decorated;

    public function __construct(YamlExtractor $extractor, PropertyNameCollectionFactoryInterface $decorated = null)
    {
        $this->extractor = $extractor;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $propertyNames = [];

        if ($this->decorated) {
            try {
                foreach ($propertyNameCollection = $this->decorated->create($resourceClass, $options) as $propertyName) {
                    $propertyNames[$propertyName] = true;
                }
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

        $resources = $this->extractor->getResources();
        if (isset($resources[$resourceClass]['properties'])) {
            foreach ($resources[$resourceClass]['properties'] as $propertyName => $property) {
                $propertyNames[$propertyName] = true;
            }
        }

        return new PropertyNameCollection(array_keys($propertyNames));
    }
}
