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
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Creates a property metadata from YAML {@see Property} configuration files.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class YamlPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    private $paths;
    private $decorated;

    /**
     * @param array                                 $paths
     * @param PropertyMetadataFactoryInterface|null $decorated
     */
    public function __construct(array $paths, PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->paths = $paths;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): PropertyMetadata
    {
        $parentPropertyMetadata = null;
        if ($this->decorated) {
            try {
                $parentPropertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException $propertyNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        if (
            !property_exists($resourceClass, $property) ||
            empty($propertyMetadata = $this->getMetadata($resourceClass, $property))
        ) {
            return $this->handleNotFound($parentPropertyMetadata, $resourceClass, $property);
        }

        if ($parentPropertyMetadata) {
            return $this->update($parentPropertyMetadata, $propertyMetadata);
        }

        return new PropertyMetadata(
            null,
            $propertyMetadata['description'],
            $propertyMetadata['readable'],
            $propertyMetadata['writable'],
            $propertyMetadata['readableLink'],
            $propertyMetadata['writableLink'],
            $propertyMetadata['required'],
            $propertyMetadata['identifier'],
            $propertyMetadata['iri'],
            null,
            $propertyMetadata['attributes']
        );
    }

    /**
     * Returns the metadata from the decorated factory if available or throws an exception.
     *
     * @param PropertyMetadata|null $parentPropertyMetadata
     * @param string                $resourceClass
     * @param string                $property
     *
     * @throws PropertyNotFoundException
     *
     * @return PropertyMetadata
     */
    private function handleNotFound(PropertyMetadata $parentPropertyMetadata = null, string $resourceClass, string $property): PropertyMetadata
    {
        if ($parentPropertyMetadata) {
            return $parentPropertyMetadata;
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" of the resource class "%s" not found.', $property, $resourceClass));
    }

    /**
     * Extracts metadata from the YAML tree.
     *
     * @param string $resourceClass
     * @param string $property
     *
     * @throws ParseException
     * @throws InvalidArgumentException
     *
     * @return array
     */
    private function getMetadata(string $resourceClass, string $property): array
    {
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

                    if ($property !== $propertyName) {
                        continue;
                    }

                    return [
                        'description' => isset($propertyValues['description']) && is_scalar($propertyValues['description']) ? $propertyValues['description'] : null,
                        'readable' => isset($propertyValues['readable']) && is_bool($propertyValues['readable']) ? $propertyValues['readable'] : null,
                        'writable' => isset($propertyValues['writable']) && is_bool($propertyValues['writable']) ? $propertyValues['writable'] : null,
                        'readableLink' => isset($propertyValues['readableLink']) && is_bool($propertyValues['readableLink']) ? $propertyValues['readableLink'] : null,
                        'writableLink' => isset($propertyValues['writableLink']) && is_bool($propertyValues['writableLink']) ? $propertyValues['writableLink'] : null,
                        'required' => isset($propertyValues['required']) && is_bool($propertyValues['required']) ? $propertyValues['required'] : null,
                        'identifier' => isset($propertyValues['identifier']) && is_bool($propertyValues['identifier']) ? $propertyValues['identifier'] : null,
                        'iri' => isset($propertyValues['iri']) && is_scalar($propertyValues['iri']) ? $propertyValues['iri'] : null,
                        'attributes' => $propertyValues['attributes'] ?? null,
                    ];
                }
            }
        }

        return [];
    }

    /**
     * Creates a new instance of metadata if the property is not already set.
     *
     * @param PropertyMetadata $propertyMetadata
     * @param array            $metadata
     *
     * @return PropertyMetadata
     */
    private function update(PropertyMetadata $propertyMetadata, array $metadata): PropertyMetadata
    {
        $metadataAccessors = [
            'description' => 'get',
            'readable' => 'is',
            'writable' => 'is',
            'writableLink' => 'is',
            'readableLink' => 'is',
            'required' => 'is',
            'identifier' => 'is',
            'iri' => 'get',
            'attributes' => 'get',
        ];

        foreach ($metadataAccessors as $metadataKey => $accessorPrefix) {
            if (null === $metadata[$metadataKey] || null !== $propertyMetadata->{$accessorPrefix.ucfirst($metadataKey)}()) {
                continue;
            }

            $propertyMetadata = $propertyMetadata->{'with'.ucfirst($metadataKey)}($metadata[$metadataKey]);
        }

        return $propertyMetadata;
    }
}
