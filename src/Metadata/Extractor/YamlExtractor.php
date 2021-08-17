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
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class YamlExtractor extends AbstractExtractor
{
    /**
     * {@inheritdoc}
     */
    protected function extractPath(string $path)
    {
        try {
            $resourcesYaml = Yaml::parse((string) file_get_contents($path), Yaml::PARSE_CONSTANT);
        } catch (ParseException $e) {
            $e->setParsedFile($path);

            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }

        if (null === $resourcesYaml = $resourcesYaml['resources'] ?? $resourcesYaml) {
            return;
        }

        if (!\is_array($resourcesYaml)) {
            throw new InvalidArgumentException(sprintf('"resources" setting is expected to be null or an array, %s given in "%s".', \gettype($resourcesYaml), $path));
        }

        $this->extractResources($resourcesYaml, $path);
    }

    private function extractResources(array $resourcesYaml, string $path): void
    {
        foreach ($resourcesYaml as $resourceName => $resourceYaml) {
            $resourceName = $this->resolve($resourceName);

            if (null === $resourceYaml) {
                $resourceYaml = [[]];
            }

            if (!\array_key_exists(0, $resourceYaml)) {
                $resourceYaml = [$resourceYaml];
            }

            foreach ($resourceYaml as $key => $resourceYamlDatum) {
                if (null === $resourceYamlDatum) {
                    $resourceYamlDatum = [];
                }

                try {
                    $base = $this->getBase($resourceYamlDatum);
                    $this->resources[$resourceName][$key] = array_merge($base, [
                        'operations' => $this->getOperations($resourceYamlDatum, $base),
                        'graphQlOperations' => $this->getGraphQlOperations($resourceYamlDatum, $base),
                    ]);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException(sprintf('%s in "%s" (%s).', $exception->getMessage(), $resourceName, $path));
                }
            }
        }
    }

    private function getProperties(array $resource): ?array
    {
        if (!\array_key_exists('properties', $resource)) {
            return null;
        }

        $properties = [];
        foreach ($resource['properties'] as $propertyName => $propertyValues) {
            if (null === $propertyValues) {
                $properties[$propertyName] = null;

                continue;
            }

            if (!\is_array($propertyValues)) {
                throw new InvalidArgumentException(sprintf('"%s" setting is expected to be null or an array, %s given.', $propertyName, \gettype($propertyValues)));
            }

            $properties[$propertyName] = [
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
                'jsonldContext' => $this->getAttribute($propertyValues, 'jsonldContext'),
                'openapiContext' => $this->getAttribute($propertyValues, 'openapiContext'),
                'types' => $this->getAttribute($propertyValues, 'types', []),
                'extraProperties' => $this->getAttribute($propertyValues, 'extraProperties'),
                'defaults' => $this->getAttribute($propertyValues, 'defaults'),
                'example' => $this->getAttribute($propertyValues, 'example', null),
                'builtinTypes' => $this->getAttribute($propertyValues, 'builtinTypes', []),
                'schema' => $this->getAttribute($propertyValues, 'schema', null),
            ];
        }

        return $properties;
    }

    private function getBase(array $resource): array
    {
        return [
            'uriTemplate' => $this->phpize($resource, 'uriTemplate', 'string'),
            'shortName' => $this->phpize($resource, 'shortName', 'string'),
            'description' => $this->phpize($resource, 'description', 'string'),
            'routePrefix' => $this->phpize($resource, 'routePrefix', 'string', ''),
            'stateless' => $this->phpize($resource, 'stateless', 'bool'),
            'sunset' => $this->phpize($resource, 'sunset', 'string'),
            'acceptPatch' => $this->phpize($resource, 'acceptPatch', 'string'),
            'host' => $this->phpize($resource, 'host', 'string', ''),
            'condition' => $this->phpize($resource, 'condition', 'string', ''),
            'controller' => $this->phpize($resource, 'controller', 'string', ''),
            'urlGenerationStrategy' => $this->phpize($resource, 'urlGenerationStrategy', 'integer'),
            'deprecationReason' => $this->phpize($resource, 'deprecationReason', 'string'),
            'elasticsearch' => $this->phpize($resource, 'elasticsearch', 'bool'),
            'fetchPartial' => $this->phpize($resource, 'fetchPartial', 'bool'),
            'forceEager' => $this->phpize($resource, 'forceEager', 'bool'),
            'paginationClientEnabled' => $this->phpize($resource, 'paginationClientEnabled', 'bool'),
            'paginationClientItemsPerPage' => $this->phpize($resource, 'paginationClientItemsPerPage', 'bool'),
            'paginationClientPartial' => $this->phpize($resource, 'paginationClientPartial', 'bool'),
            'paginationEnabled' => $this->phpize($resource, 'paginationEnabled', 'bool'),
            'paginationFetchJoinCollection' => $this->phpize($resource, 'paginationFetchJoinCollection', 'bool'),
            'paginationUseOutputWalkers' => $this->phpize($resource, 'paginationUseOutputWalkers', 'bool'),
            'paginationItemsPerPage' => $this->phpize($resource, 'paginationItemsPerPage', 'integer'),
            'paginationMaximumItemsPerPage' => $this->phpize($resource, 'paginationMaximumItemsPerPage', 'integer'),
            'paginationPartial' => $this->phpize($resource, 'paginationPartial', 'bool'),
            'paginationType' => $this->phpize($resource, 'paginationType', 'string'),
            'security' => $this->phpize($resource, 'security', 'string'),
            'securityMessage' => $this->phpize($resource, 'securityMessage', 'string'),
            'securityPostDenormalize' => $this->phpize($resource, 'securityPostDenormalize', 'string'),
            'securityPostDenormalizeMessage' => $this->phpize($resource, 'securityPostDenormalizeMessage', 'string'),
            'compositeIdentifiers' => $this->phpize($resource, 'compositeIdentifiers', 'bool'),
            'queryParameterValidationEnabled' => $this->phpize($resource, 'queryParameterValidationEnabled', 'bool'),
            'input' => $this->phpize($resource, 'input', 'bool|string'),
            'output' => $this->phpize($resource, 'output', 'bool|string'),
            'types' => $this->getAttribute($resource, 'types', []),
            'cacheHeaders' => $this->getAttribute($resource, 'cacheHeaders', []),
            'normalizationContext' => $this->getAttribute($resource, 'normalizationContext', []),
            'denormalizationContext' => $this->getAttribute($resource, 'denormalizationContext', []),
            'hydraContext' => $this->getAttribute($resource, 'hydraContext', []),
            'openapiContext' => $this->getAttribute($resource, 'openapiContext', []),
            'validationContext' => $this->getAttribute($resource, 'validationContext', []),
            'filters' => $this->getAttribute($resource, 'filters'),
            'order' => $this->getAttribute($resource, 'order'),
            'paginationViaCursor' => $this->getAttribute($resource, 'paginationViaCursor', []),
            'exceptionToStatus' => $this->getAttribute($resource, 'exceptionToStatus', []),
            'extraProperties' => $this->getAttribute($resource, 'extraProperties', []),
            'defaults' => $this->getAttribute($resource, 'defaults', []),
            'requirements' => $this->getAttribute($resource, 'requirements', []),
            'options' => $this->getAttribute($resource, 'options', []),
            'status' => $this->phpize($resource, 'status', 'integer'),
            'schemes' => $this->getAttribute($resource, 'schemes'),
            'properties' => $this->getProperties($resource),
            'formats' => $this->getAttribute($resource, 'formats', null),
            'identifiers' => $this->getAttribute($resource, 'identifiers', null),
            'inputFormats' => $this->getAttribute($resource, 'inputFormats', null),
            'outputFormats' => $this->getAttribute($resource, 'outputFormats', null),
            'mercure' => $this->getMercure($resource),
            'messenger' => $this->getMessenger($resource),
        ];
    }

    /**
     * @return bool|string|string[]|null
     */
    private function getMercure(array $resource)
    {
        if (!\array_key_exists('mercure', $resource)) {
            return null;
        }

        if (\is_string($resource['mercure'])) {
            return $this->phpize($resource, 'mercure', 'bool|string');
        }

        return $resource['mercure'];
    }

    /**
     * @return array|bool|string|null
     */
    private function getMessenger(array $resource)
    {
        if (!\array_key_exists('messenger', $resource)) {
            return null;
        }

        return $this->phpize($resource, 'messenger', 'bool|string');
    }

    private function getOperations(array $resource, array $root): ?array
    {
        if (!\array_key_exists('operations', $resource)) {
            return null;
        }

        $data = [];
        foreach ($resource['operations'] as $class => $operation) {
            if (null === $operation) {
                $operation = [];
            }

            if (\array_key_exists('class', $operation)) {
                if (!\array_key_exists('name', $operation) && \is_string($class)) {
                    $operation['name'] = $class;
                }
                $class = $operation['class'];
            }

            if (empty($class)) {
                throw new InvalidArgumentException('Missing "class" attribute');
            }

            if (!class_exists($class)) {
                throw new InvalidArgumentException(sprintf('Operation class "%s" does not exist', $class));
            }

            $datum = $this->getBase($operation);
            foreach ($datum as $key => $value) {
                if (empty($value)) {
                    $datum[$key] = $root[$key];
                }
            }

            $data[] = array_merge($datum, [
                'read' => $this->phpize($operation, 'read', 'bool', true),
                'deserialize' => $this->phpize($operation, 'deserialize', 'bool', true),
                'validate' => $this->phpize($operation, 'validate', 'bool', true),
                'write' => $this->phpize($operation, 'write', 'bool', true),
                'serialize' => $this->phpize($operation, 'serialize', 'bool', true),
                'queryParameterValidate' => $this->phpize($operation, 'queryParameterValidate', 'bool', true),
                'priority' => $this->phpize($operation, 'priority', 'integer', 0),
                'name' => $this->phpize($operation, 'name', 'string', ''),
                'class' => (string) $class,
            ]);
        }

        return $data;
    }

    private function getGraphQlOperations(array $resource, array $root): ?array
    {
        if (!\array_key_exists('graphQlOperations', $resource)) {
            return null;
        }

        $data = [];
        foreach ($resource['graphQlOperations'] as $operation) {
            $datum = $this->getBase($operation);
            foreach ($datum as $key => $value) {
                if (empty($value)) {
                    $datum[$key] = $root[$key];
                }
            }

            $data[] = array_merge($datum, [
                'resolver' => $this->phpize($operation, 'resolver', 'string'),
                'class' => $this->phpize($operation, 'class', 'string'),
                'compositeIdentifier' => $this->phpize($operation, 'compositeIdentifier', 'bool'),
                'paginationEnabled' => $this->phpize($operation, 'paginationEnabled', 'bool'),
                'paginationType' => $this->phpize($operation, 'paginationType', 'string'),
                'paginationItemsPerPage' => $this->phpize($operation, 'paginationItemsPerPage', 'integer'),
                'paginationMaximumItemsPerPage' => $this->phpize($operation, 'paginationMaximumItemsPerPage', 'integer'),
                'paginationPartial' => $this->phpize($operation, 'paginationPartial', 'bool'),
                'paginationClientEnabled' => $this->phpize($operation, 'paginationClientEnabled', 'bool'),
                'paginationClientItemsPerPage' => $this->phpize($operation, 'paginationClientItemsPerPage', 'bool'),
                'paginationClientPartial' => $this->phpize($operation, 'paginationClientPartial', 'bool'),
                'paginationFetchJoinCollection' => $this->phpize($operation, 'paginationFetchJoinCollection', 'bool'),
                'paginationUseOutputWalkers' => $this->phpize($operation, 'paginationUseOutputWalkers', 'bool'),
                'description' => $this->phpize($operation, 'description', 'string'),
                'security' => $this->phpize($operation, 'security', 'string'),
                'securityMessage' => $this->phpize($operation, 'securityMessage', 'string'),
                'securityPostDenormalize' => $this->phpize($operation, 'securityPostDenormalize', 'string'),
                'securityPostDenormalizeMessage' => $this->phpize($operation, 'securityPostDenormalizeMessage', 'string'),
                'deprecationReason' => $this->phpize($operation, 'deprecationReason', 'string'),
                'input' => $this->phpize($operation, 'input', 'bool|string'),
                'output' => $this->phpize($operation, 'output', 'bool|string'),
                'mercure' => $this->getMercure($operation),
                'messenger' => $this->getMessenger($operation),
                'elasticsearch' => $this->phpize($operation, 'elasticsearch', 'bool'),
                'urlGenerationStrategy' => $this->phpize($operation, 'urlGenerationStrategy', 'integer'),
                'read' => $this->phpize($operation, 'read', 'bool'),
                'deserialize' => $this->phpize($operation, 'deserialize', 'bool'),
                'validate' => $this->phpize($operation, 'validate', 'bool'),
                'write' => $this->phpize($operation, 'write', 'bool'),
                'serialize' => $this->phpize($operation, 'serialize', 'bool'),
                'fetchPartial' => $this->phpize($operation, 'fetchPartial', 'bool'),
                'forceEager' => $this->phpize($operation, 'forceEager', 'bool'),
                'priority' => $this->phpize($operation, 'priority', 'integer'),
            ]);
        }

        return $data;
    }

    private function getAttribute(array $resource, string $key, $default = [])
    {
        if (!\array_key_exists($key, $resource)) {
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
                return \in_array((string) $resource[$key], ['true', 'false'], true) ? $this->phpize($resource, $key, 'bool') : $this->phpize($resource, $key, 'string');
            case 'string':
                return (string) $resource[$key];
            case 'integer':
                return (int) $resource[$key];
            case 'bool':
                return (bool) $resource[$key];
        }

        throw new InvalidArgumentException(sprintf('The property "%s" must be a "%s", "%s" given.', $key, $type, \gettype($resource[$key])));
    }
}
