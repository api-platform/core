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

namespace ApiPlatform\Metadata\Extractor;

use ApiPlatform\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Extracts an array of metadata from a list of YAML files.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class YamlPropertyExtractor extends AbstractPropertyExtractor
{
    /**
     * {@inheritdoc}
     */
    protected function extractPath(string $path)
    {
        try {
            $propertiesYaml = Yaml::parse((string) file_get_contents($path), Yaml::PARSE_CONSTANT);
        } catch (ParseException $e) {
            $e->setParsedFile($path);

            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if (null === $propertiesYaml = $propertiesYaml['properties'] ?? $propertiesYaml) {
            return;
        }

        if (!\is_array($propertiesYaml)) {
            throw new InvalidArgumentException(sprintf('"properties" setting is expected to be null or an array, %s given in "%s".', \gettype($propertiesYaml), $path));
        }

        $this->buildProperties($propertiesYaml);
    }

    private function buildProperties(array $resourcesYaml): void
    {
        foreach ($resourcesYaml as $resourceName => $resourceYaml) {
            if (null === $resourceYaml) {
                continue;
            }

            $resourceName = $this->resolve($resourceName);

            foreach ($resourceYaml as $propertyName => $propertyValues) {
                if (null === $propertyValues) {
                    $this->properties[$resourceName][$propertyName] = null;
                    continue;
                }

                if (!\is_array($propertyValues)) {
                    throw new InvalidArgumentException(sprintf('"%s" setting is expected to be null or an array, %s given.', $propertyName, \gettype($propertyValues)));
                }

                $this->properties[$resourceName][$propertyName] = [
                    'description' => $this->phpize($propertyValues, 'description', 'string'),
                    'readable' => $this->phpize($propertyValues, 'readable', 'bool'),
                    'writable' => $this->phpize($propertyValues, 'writable', 'bool'),
                    'readableLink' => $this->phpize($propertyValues, 'readableLink', 'bool'),
                    'writableLink' => $this->phpize($propertyValues, 'writableLink', 'bool'),
                    'required' => $this->phpize($propertyValues, 'required', 'bool'),
                    'identifier' => $this->phpize($propertyValues, 'identifier', 'bool'),
                    'deprecationReason' => $this->phpize($propertyValues, 'deprecationReason', 'string'),
                    'fetchable' => $this->phpize($propertyValues, 'fetchable', 'bool'),
                    'fetchEager' => $this->phpize($propertyValues, 'fetchEager', 'bool'),
                    'push' => $this->phpize($propertyValues, 'push', 'bool'),
                    'security' => $this->phpize($propertyValues, 'security', 'string'),
                    'securityPostDenormalize' => $this->phpize($propertyValues, 'securityPostDenormalize', 'string'),
                    'initializable' => $this->phpize($propertyValues, 'initializable', 'bool'),
                    'iris' => $this->buildAttribute($propertyValues, 'iris'),
                    'jsonldContext' => $this->buildAttribute($propertyValues, 'jsonldContext'),
                    'openapiContext' => $this->buildAttribute($propertyValues, 'openapiContext'),
                    'jsonSchemaContext' => $this->buildAttribute($propertyValues, 'jsonSchemaContext'),
                    'types' => $this->buildAttribute($propertyValues, 'types'),
                    'extraProperties' => $this->buildAttribute($propertyValues, 'extraProperties'),
                    'default' => $propertyValues['default'] ?? null,
                    'example' => $propertyValues['example'] ?? null,
                    'builtinTypes' => $this->buildAttribute($propertyValues, 'builtinTypes'),
                    'schema' => $this->buildAttribute($propertyValues, 'schema'),
                    'genId' => $this->phpize($propertyValues, 'genId', 'bool'),
                ];
            }
        }
    }

    private function buildAttribute(array $resource, string $key, $default = null)
    {
        if (empty($resource[$key])) {
            return $default;
        }

        if (!\is_array($resource[$key])) {
            throw new InvalidArgumentException(sprintf('"%s" setting is expected to be an array, %s given', $key, \gettype($resource[$key])));
        }

        return $resource[$key];
    }

    /**
     * Transforms an XML attribute's value in a PHP value.
     *
     * @param mixed|null $default
     *
     * @return string|int|bool|array|null
     */
    private function phpize(?array $resource, string $key, string $type, $default = null)
    {
        if (!isset($resource[$key])) {
            return $default;
        }

        switch ($type) {
            case 'bool|string':
                return \in_array($resource[$key], ['1', '0', 1, 0, 'true', 'false', true, false], true) ? $this->phpize($resource, $key, 'bool') : $this->phpize($resource, $key, 'string');
            case 'string':
                return (string) $resource[$key];
            case 'integer':
                return (int) $resource[$key];
            case 'bool':
                return \in_array($resource[$key], ['1', 'true', 1, true], false);
        }

        throw new InvalidArgumentException(sprintf('The property "%s" must be a "%s", "%s" given.', $key, $type, \gettype($resource[$key])));
    }
}
