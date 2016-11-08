<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Metadata;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Converts a list of YAML metadata files in a PHP array.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
final class YamlExtractor
{
    private $paths;
    private $resources;

    /**
     * @param string[] $paths
     */
    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * Parses all metadata files and convert them in an array.
     *
     * @throws ParseException
     *
     * @return array
     */
    public function getResources(): array
    {
        if (null !== $this->resources) {
            return $this->resources;
        }

        $this->resources = [];
        foreach ($this->paths as $path) {
            try {
                $resourcesYaml = Yaml::parse(file_get_contents($path));
            } catch (ParseException $parseException) {
                $parseException->setParsedFile($path);

                throw $parseException;
            }

            if (null === $resourcesYaml = $resourcesYaml['resources'] ?? $resourcesYaml) {
                continue;
            }

            if (!is_array($resourcesYaml)) {
                throw new InvalidArgumentException(sprintf('"resources" setting is expected to be null or an array, %s given in "%s".', gettype($resourcesYaml), $path));
            }

            $this->extractResources($resourcesYaml, $path);
        }

        return $this->resources;
    }

    private function extractResources(array $resourcesYaml, string $path)
    {
        foreach ($resourcesYaml as $resourceName => $resourceYaml) {
            if (null === $resourceYaml) {
                $this->resources[$resourceName] = null;

                continue;
            }

            if (!is_array($resourceYaml)) {
                throw new InvalidArgumentException(sprintf('"%s" setting is expected to be null or an array, %s given in "%s".', $resourceName, gettype($resourceYaml), $path));
            }

            $this->resources[$resourceName] = [
                'shortName' => $resourceYaml['shortName'] ?? null,
                'description' => $resourceYaml['description'] ?? null,
                'iri' => $resourceYaml['iri'] ?? null,
                'itemOperations' => $resourceYaml['itemOperations'] ?? null,
                'collectionOperations' => $resourceYaml['collectionOperations'] ?? null,
                'attributes' => $resourceYaml['attributes'] ?? null,
            ];

            if (!isset($resourceYaml['properties'])) {
                $this->resources[$resourceName]['properties'] = null;

                continue;
            }

            if (!is_array($resourceYaml['properties'])) {
                throw new InvalidArgumentException(sprintf('"properties" setting is expected to be null or an array, %s given in "%s".', gettype($resourceYaml['properties']), $path));
            }

            $this->extractProperties($resourceYaml, $resourceName, $path);
        }
    }

    private function extractProperties(array $resourceYaml, string $resourceName, string $path)
    {
        foreach ($resourceYaml['properties'] as $propertyName => $propertyValues) {
            if (null === $propertyValues) {
                $this->resources[$resourceName]['properties'][$propertyName] = null;

                continue;
            }

            if (!is_array($propertyValues)) {
                throw new InvalidArgumentException(sprintf('"%s" setting is expected to be null or an array, %s given in "%s".', $propertyName, gettype($propertyValues), $path));
            }

            $this->resources[$resourceName]['properties'][$propertyName] = [
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
