<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Metadata\Extractor;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Extracts an array of metadata from a list of YAML files.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class YamlExtractor extends AbstractExtractor
{
    /**
     * {@inheritdoc}
     */
    protected function extractPath(string $path)
    {
        try {
            $resourcesYaml = Yaml::parse(file_get_contents($path));
        } catch (ParseException $e) {
            $e->setParsedFile($path);

            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if (null === $resourcesYaml = $resourcesYaml['resources'] ?? $resourcesYaml) {
            return;
        }

        if (!is_array($resourcesYaml)) {
            throw new InvalidArgumentException(sprintf('"resources" setting is expected to be null or an array, %s given in "%s".', gettype($resourcesYaml), $path));
        }

        $this->extractResources($resourcesYaml, $path);
    }

    private function extractResources(array $resourcesYaml, string $path)
    {
        foreach ($resourcesYaml as $resourceName => $resourceYaml) {
            if (null === $resourceYaml) {
                $resourceYaml = [];
            }

            if (!is_array($resourceYaml)) {
                throw new InvalidArgumentException(sprintf('"%s" setting is expected to be null or an array, %s given in "%s".', $resourceName, gettype($resourceYaml), $path));
            }

            $this->resources[$resourceName] = [
                'shortName' => $this->phpize($resourceYaml, 'shortName', 'string'),
                'description' => $this->phpize($resourceYaml, 'description', 'string'),
                'iri' => $this->phpize($resourceYaml, 'iri', 'string'),
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
                'description' => $this->phpize($propertyValues, 'description', 'string'),
                'readable' => $this->phpize($propertyValues, 'readable', 'bool'),
                'writable' => $this->phpize($propertyValues, 'writable', 'bool'),
                'readableLink' => $this->phpize($propertyValues, 'readableLink', 'bool'),
                'writableLink' => $this->phpize($propertyValues, 'writableLink', 'bool'),
                'required' => $this->phpize($propertyValues, 'required', 'bool'),
                'identifier' => $this->phpize($propertyValues, 'identifier', 'bool'),
                'iri' => $this->phpize($propertyValues, 'iri', 'string'),
                'attributes' => $propertyValues['attributes'] ?? null,
            ];
        }
    }

    /**
     * Transforms a YAML attribute's value in PHP value.
     *
     * @param array  $array
     * @param string $key
     * @param string $type
     *
     * @throws InvalidArgumentException
     *
     * @return bool|string|null
     */
    private function phpize(array $array, string $key, string $type)
    {
        if (!isset($array[$key])) {
            return;
        }

        switch ($type) {
            case 'bool':
                if (is_bool($array[$key])) {
                    return $array[$key];
                }
                break;
            case 'string':
                if (is_string($array[$key])) {
                    return $array[$key];
                }
                break;
        }

        throw new InvalidArgumentException(sprintf('The property "%s" must be a "%s", "%s" given.', $key, $type, gettype($array[$key])));
    }
}
