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
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Creates a property name collection from YAML {@see Property} configuration files.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class YamlPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
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
     * @throws ParseException
     * @throws InvalidArgumentException
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
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
                $resources = Yaml::parse(file_get_contents($path));
            } catch (ParseException $parseException) {
                $parseException->setParsedFile($path);

                throw $parseException;
            }

            if (null === $resources = $resources['resources'] ?? $resources) {
                continue;
            }

            if (!is_array($resources)) {
                throw new InvalidArgumentException(sprintf('"resources" setting is expected to be null or an array, %s given in "%s".', gettype($resources), $path));
            }

            foreach ($resources as $resourceName => $resource) {
                if (null === $resource) {
                    continue;
                }

                if (!is_array($resource)) {
                    throw new InvalidArgumentException(sprintf('"%s" setting is expected to be null or an array, %s given in "%s".', $resourceName, gettype($resource), $path));
                }

                if (!isset($resource['class'])) {
                    throw new InvalidArgumentException(sprintf('"class" setting is expected to be a string, none given in "%s".', $path));
                }

                if ($resourceClass !== $resource['class'] || !isset($resource['properties'])) {
                    continue;
                }

                if (!is_array($resource['properties'])) {
                    throw new InvalidArgumentException(sprintf('"properties" setting is expected to be null or an array, %s given in "%s".', gettype($resource['properties']), $path));
                }

                foreach ($resource['properties'] as $propertyName => $propertyValues) {
                    if (null === $propertyValues) {
                        continue;
                    }

                    if (!is_array($propertyValues)) {
                        throw new InvalidArgumentException(sprintf('"%s" setting is expected to be null or an array, %s given in "%s".', $propertyName, gettype($propertyValues), $path));
                    }

                    $propertyNames[$propertyName] = true;
                }
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
