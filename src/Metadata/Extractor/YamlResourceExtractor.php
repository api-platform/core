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
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
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
final class YamlResourceExtractor extends AbstractResourceExtractor
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

        $this->buildResources($resourcesYaml, $path);
    }

    private function buildResources(array $resourcesYaml, string $path): void
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
                    $base = $this->buildExtendedBase($resourceYamlDatum);
                    $this->resources[$resourceName][$key] = array_merge($base, [
                        'operations' => $this->buildOperations($resourceYamlDatum, $base),
                        'graphQlOperations' => $this->buildGraphQlOperations($resourceYamlDatum, $base),
                    ]);
                } catch (InvalidArgumentException $exception) {
                    throw new InvalidArgumentException(sprintf('%s in "%s" (%s).', $exception->getMessage(), $resourceName, $path));
                }
            }
        }
    }

    private function buildExtendedBase(array $resource): array
    {
        return array_merge($this->buildBase($resource), [
            'uriTemplate' => $this->phpize($resource, 'uriTemplate', 'string'),
            'routePrefix' => $this->phpize($resource, 'routePrefix', 'string'),
            'stateless' => $this->phpize($resource, 'stateless', 'bool'),
            'sunset' => $this->phpize($resource, 'sunset', 'string'),
            'acceptPatch' => $this->phpize($resource, 'acceptPatch', 'string'),
            'host' => $this->phpize($resource, 'host', 'string'),
            'condition' => $this->phpize($resource, 'condition', 'string'),
            'controller' => $this->phpize($resource, 'controller', 'string'),
            'compositeIdentifier' => $this->phpize($resource, 'compositeIdentifier', 'bool'),
            'queryParameterValidationEnabled' => $this->phpize($resource, 'queryParameterValidationEnabled', 'bool'),
            'types' => $this->buildAttribute($resource, 'types'),
            'cacheHeaders' => $this->buildAttribute($resource, 'cacheHeaders'),
            'hydraContext' => $this->buildAttribute($resource, 'hydraContext'),
            'openapiContext' => $this->buildAttribute($resource, 'openapiContext'),
            'paginationViaCursor' => $this->buildAttribute($resource, 'paginationViaCursor'),
            'exceptionToStatus' => $this->buildAttribute($resource, 'exceptionToStatus'),
            'defaults' => $this->buildAttribute($resource, 'defaults'),
            'requirements' => $this->buildAttribute($resource, 'requirements'),
            'options' => $this->buildAttribute($resource, 'options'),
            'status' => $this->phpize($resource, 'status', 'integer'),
            'schemes' => $this->buildAttribute($resource, 'schemes'),
            'formats' => $this->buildAttribute($resource, 'formats'),
            'uriVariables' => $this->buildUriVariables($resource),
            'inputFormats' => $this->buildAttribute($resource, 'inputFormats'),
            'outputFormats' => $this->buildAttribute($resource, 'outputFormats'),
        ]);
    }

    private function buildBase(array $resource): array
    {
        return [
            'shortName' => $this->phpize($resource, 'shortName', 'string'),
            'description' => $this->phpize($resource, 'description', 'string'),
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
            'securityPostValidation' => $this->phpize($resource, 'securityPostValidation', 'string'),
            'securityPostValidationMessage' => $this->phpize($resource, 'securityPostValidationMessage', 'string'),
            'input' => $this->phpize($resource, 'input', 'string'),
            'output' => $this->phpize($resource, 'output', 'string'),
            'normalizationContext' => $this->buildAttribute($resource, 'normalizationContext'),
            'denormalizationContext' => $this->buildAttribute($resource, 'denormalizationContext'),
            'validationContext' => $this->buildAttribute($resource, 'validationContext'),
            'filters' => $this->buildAttribute($resource, 'filters'),
            'order' => $this->buildAttribute($resource, 'order'),
            'extraProperties' => $this->buildAttribute($resource, 'extraProperties'),
            'mercure' => $this->buildMercure($resource),
            'messenger' => $this->buildMessenger($resource),
        ];
    }

    private function buildUriVariables(array $resource): ?array
    {
        if (!\array_key_exists('uriVariables', $resource)) {
            return null;
        }

        $uriVariables = [];
        foreach ($resource['uriVariables'] as $parameterName => $data) {
            if (\is_string($data)) {
                $uriVariables[$data] = $data;
                continue;
            }

            if (isset($data['class']) || isset($data[0])) {
                $uriVariables[$parameterName]['class'] = $data['class'] ?? $data[0];
            }
            if (isset($data['property']) || isset($data[1])) {
                $uriVariables[$parameterName]['property'] = $data['property'] ?? $data[1];
            }
            if (isset($data['inverseProperty'])) {
                $uriVariables[$parameterName]['inverse_property'] = $data['inverseProperty'];
            }
            if (isset($data['identifiers'])) {
                $uriVariables[$parameterName]['identifiers'] = $data['identifiers'];
            }
            if (isset($data['compositeIdentifier'])) {
                $uriVariables[$parameterName]['composite_identifier'] = $data['compositeIdentifier'];
            }
        }

        return $uriVariables;
    }

    /**
     * @return bool|string|string[]|null
     */
    private function buildMercure(array $resource)
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
    private function buildMessenger(array $resource)
    {
        if (!\array_key_exists('messenger', $resource)) {
            return null;
        }

        return $this->phpize($resource, 'messenger', 'bool|string');
    }

    private function buildOperations(array $resource, array $root): ?array
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

            $datum = $this->buildExtendedBase($operation);
            foreach ($datum as $key => $value) {
                if (null === $value) {
                    $datum[$key] = $root[$key];
                }
            }

            $data[] = array_merge($datum, [
                'read' => $this->phpize($operation, 'read', 'bool'),
                'deserialize' => $this->phpize($operation, 'deserialize', 'bool'),
                'validate' => $this->phpize($operation, 'validate', 'bool'),
                'write' => $this->phpize($operation, 'write', 'bool'),
                'serialize' => $this->phpize($operation, 'serialize', 'bool'),
                'queryParameterValidate' => $this->phpize($operation, 'queryParameterValidate', 'bool'),
                'priority' => $this->phpize($operation, 'priority', 'integer'),
                'name' => $this->phpize($operation, 'name', 'string'),
                'class' => (string) $class,
            ]);
        }

        return $data;
    }

    private function buildGraphQlOperations(array $resource, array $root): ?array
    {
        if (!\array_key_exists('graphQlOperations', $resource)) {
            return null;
        }

        $data = [];
        foreach (['mutations' => Mutation::class, 'queries' => Query::class, 'subscriptions' => Subscription::class] as $type => $class) {
            foreach ($resource['graphQlOperations'][$type] as $operation) {
                $datum = $this->buildBase($operation);
                foreach ($datum as $key => $value) {
                    if (null === $value) {
                        $datum[$key] = $root[$key];
                    }
                }

                $data[] = array_merge($datum, [
                    'graphql_operation_class' => $class,
                    'resolver' => $this->phpize($operation, 'resolver', 'string'),
                    'collection' => $this->phpize($operation, 'collection', 'bool'),
                    'args' => $operation['args'] ?? null,
                    'class' => $this->phpize($operation, 'class', 'string'),
                    'read' => $this->phpize($operation, 'read', 'bool'),
                    'deserialize' => $this->phpize($operation, 'deserialize', 'bool'),
                    'validate' => $this->phpize($operation, 'validate', 'bool'),
                    'write' => $this->phpize($operation, 'write', 'bool'),
                    'serialize' => $this->phpize($operation, 'serialize', 'bool'),
                    'priority' => $this->phpize($operation, 'priority', 'integer'),
                ]);
            }
        }

        return $data;
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
