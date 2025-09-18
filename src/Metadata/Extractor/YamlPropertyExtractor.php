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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
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
    protected function extractPath(string $path): void
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
            throw new InvalidArgumentException(\sprintf('"properties" setting is expected to be null or an array, %s given in "%s".', \gettype($propertiesYaml), $path));
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
                    throw new InvalidArgumentException(\sprintf('"%s" setting is expected to be null or an array, %s given.', $propertyName, \gettype($propertyValues)));
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
                    'uriTemplate' => $this->phpize($propertyValues, 'uriTemplate', 'string'),
                    'property' => $this->phpize($propertyValues, 'property', 'string'),
                    'nativeType' => $this->phpize($propertyValues, 'nativeType', 'string'),
                ];
            }
        }
    }

    private function buildAttribute(array $resource, string $key): ?array
    {
        if (empty($resource[$key])) {
            return null;
        }

        if (!\is_array($resource[$key])) {
            throw new InvalidArgumentException(\sprintf('"%s" setting is expected to be an array, %s given', $key, \gettype($resource[$key])));
        }

        return $resource[$key];
    }

    /**
     * Transforms an XML attribute's value in a PHP value.
     */
    private function phpize(?array $resource, string $key, string $type, mixed $default = null): array|bool|int|string|null
    {
        if (!isset($resource[$key])) {
            return $default;
        }

        return match ($type) {
            'bool|string' => \in_array($resource[$key], ['1', '0', 1, 0, 'true', 'false', true, false], true) ? $this->phpize($resource, $key, 'bool') : $this->phpize($resource, $key, 'string'),
            'string' => (string) $resource[$key],
            'integer' => (int) $resource[$key],
            'bool' => \in_array($resource[$key], ['1', 'true', 1, true], false),
            default => throw new InvalidArgumentException(\sprintf('The property "%s" must be a "%s", "%s" given.', $key, $type, \gettype($resource[$key]))),
        };
    }
}
