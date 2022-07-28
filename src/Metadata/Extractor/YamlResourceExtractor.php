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
use ApiPlatform\Metadata\GraphQl\DeleteMutation;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
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
    use ResourceExtractorTrait;

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

    /**
     * @return array{shortName: bool|int|string|mixed[]|null, description: bool|int|string|mixed[]|null, urlGenerationStrategy: bool|int|string|mixed[]|null, deprecationReason: bool|int|string|mixed[]|null, elasticsearch: bool|int|string|mixed[]|null, fetchPartial: bool|int|string|mixed[]|null, forceEager: bool|int|string|mixed[]|null, paginationClientEnabled: bool|int|string|mixed[]|null, paginationClientItemsPerPage: bool|int|string|mixed[]|null, paginationClientPartial: bool|int|string|mixed[]|null, paginationEnabled: bool|int|string|mixed[]|null, paginationFetchJoinCollection: bool|int|string|mixed[]|null, paginationUseOutputWalkers: bool|int|string|mixed[]|null, paginationItemsPerPage: bool|int|string|mixed[]|null, paginationMaximumItemsPerPage: bool|int|string|mixed[]|null, paginationPartial: bool|int|string|mixed[]|null, paginationType: bool|int|string|mixed[]|null, processor: bool|int|string|mixed[]|null, provider: bool|int|string|mixed[]|null, security: bool|int|string|mixed[]|null, securityMessage: bool|int|string|mixed[]|null, securityPostDenormalize: bool|int|string|mixed[]|null, securityPostDenormalizeMessage: bool|int|string|mixed[]|null, securityPostValidation: bool|int|string|mixed[]|null, securityPostValidationMessage: bool|int|string|mixed[]|null, input: bool|int|string|mixed[]|null, output: bool|int|string|mixed[]|null, normalizationContext: mixed[]|null, denormalizationContext: mixed[]|null, validationContext: mixed[]|null, filters: mixed[]|null, order: mixed[]|null, extraProperties: mixed[]|null, mercure: bool|string|string[]|null, messenger: bool|string|mixed[]|null, read: bool|int|string|mixed[]|null, write: bool|int|string|mixed[]|null, uriTemplate: mixed[]|bool|int|string|null, routePrefix: mixed[]|bool|int|string|null, stateless: mixed[]|bool|int|string|null, sunset: mixed[]|bool|int|string|null, acceptPatch: mixed[]|bool|int|string|null, host: mixed[]|bool|int|string|null, condition: mixed[]|bool|int|string|null, controller: mixed[]|bool|int|string|null, queryParameterValidationEnabled: mixed[]|bool|int|string|null, types: mixed[]|null, cacheHeaders: mixed[]|null, hydraContext: mixed[]|null, openapiContext: mixed[]|null, paginationViaCursor: mixed[]|null, exceptionToStatus: mixed[]|null, defaults: mixed[]|null, requirements: mixed[]|null, options: mixed[]|null, status: mixed[]|bool|int|string|null, schemes: mixed[]|null, formats: mixed[]|null, uriVariables: mixed[]|null, inputFormats: mixed[]|null, outputFormats: mixed[]|null}
     */
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
            'queryParameterValidationEnabled' => $this->phpize($resource, 'queryParameterValidationEnabled', 'bool'),
            'types' => $this->buildArrayValue($resource, 'types'),
            'cacheHeaders' => $this->buildArrayValue($resource, 'cacheHeaders'),
            'hydraContext' => $this->buildArrayValue($resource, 'hydraContext'),
            'openapiContext' => $this->buildArrayValue($resource, 'openapiContext'),
            'paginationViaCursor' => $this->buildArrayValue($resource, 'paginationViaCursor'),
            'exceptionToStatus' => $this->buildArrayValue($resource, 'exceptionToStatus'),
            'defaults' => $this->buildArrayValue($resource, 'defaults'),
            'requirements' => $this->buildArrayValue($resource, 'requirements'),
            'options' => $this->buildArrayValue($resource, 'options'),
            'status' => $this->phpize($resource, 'status', 'integer'),
            'schemes' => $this->buildArrayValue($resource, 'schemes'),
            'formats' => $this->buildArrayValue($resource, 'formats'),
            'uriVariables' => $this->buildUriVariables($resource),
            'inputFormats' => $this->buildArrayValue($resource, 'inputFormats'),
            'outputFormats' => $this->buildArrayValue($resource, 'outputFormats'),
        ]);
    }

    /**
     * @return array{shortName: mixed[]|bool|int|string|null, description: mixed[]|bool|int|string|null, urlGenerationStrategy: mixed[]|bool|int|string|null, deprecationReason: mixed[]|bool|int|string|null, elasticsearch: mixed[]|bool|int|string|null, fetchPartial: mixed[]|bool|int|string|null, forceEager: mixed[]|bool|int|string|null, paginationClientEnabled: mixed[]|bool|int|string|null, paginationClientItemsPerPage: mixed[]|bool|int|string|null, paginationClientPartial: mixed[]|bool|int|string|null, paginationEnabled: mixed[]|bool|int|string|null, paginationFetchJoinCollection: mixed[]|bool|int|string|null, paginationUseOutputWalkers: mixed[]|bool|int|string|null, paginationItemsPerPage: mixed[]|bool|int|string|null, paginationMaximumItemsPerPage: mixed[]|bool|int|string|null, paginationPartial: mixed[]|bool|int|string|null, paginationType: mixed[]|bool|int|string|null, processor: mixed[]|bool|int|string|null, provider: mixed[]|bool|int|string|null, security: mixed[]|bool|int|string|null, securityMessage: mixed[]|bool|int|string|null, securityPostDenormalize: mixed[]|bool|int|string|null, securityPostDenormalizeMessage: mixed[]|bool|int|string|null, securityPostValidation: mixed[]|bool|int|string|null, securityPostValidationMessage: mixed[]|bool|int|string|null, input: mixed[]|bool|int|string|null, output: mixed[]|bool|int|string|null, normalizationContext: mixed[]|null, denormalizationContext: mixed[]|null, validationContext: mixed[]|null, filters: mixed[]|null, order: mixed[]|null, extraProperties: mixed[]|null, mercure: string[]|bool|string|null, messenger: mixed[]|bool|string|null, read: mixed[]|bool|int|string|null, write: mixed[]|bool|int|string|null}
     */
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
            'processor' => $this->phpize($resource, 'processor', 'string'),
            'provider' => $this->phpize($resource, 'provider', 'string'),
            'security' => $this->phpize($resource, 'security', 'string'),
            'securityMessage' => $this->phpize($resource, 'securityMessage', 'string'),
            'securityPostDenormalize' => $this->phpize($resource, 'securityPostDenormalize', 'string'),
            'securityPostDenormalizeMessage' => $this->phpize($resource, 'securityPostDenormalizeMessage', 'string'),
            'securityPostValidation' => $this->phpize($resource, 'securityPostValidation', 'string'),
            'securityPostValidationMessage' => $this->phpize($resource, 'securityPostValidationMessage', 'string'),
            'input' => $this->phpize($resource, 'input', 'string'),
            'output' => $this->phpize($resource, 'output', 'string'),
            'normalizationContext' => $this->buildArrayValue($resource, 'normalizationContext'),
            'denormalizationContext' => $this->buildArrayValue($resource, 'denormalizationContext'),
            'validationContext' => $this->buildArrayValue($resource, 'validationContext'),
            'filters' => $this->buildArrayValue($resource, 'filters'),
            'order' => $this->buildArrayValue($resource, 'order'),
            'extraProperties' => $this->buildArrayValue($resource, 'extraProperties'),
            'mercure' => $this->buildMercure($resource),
            'messenger' => $this->buildMessenger($resource),
            'read' => $this->phpize($resource, 'read', 'bool'),
            'write' => $this->phpize($resource, 'write', 'bool'),
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

            if (2 === (is_countable($data) ? \count($data) : 0) && isset($data[0]) && isset($data[1])) {
                $data['fromClass'] = $data[0];
                $data['fromProperty'] = $data[1];
                unset($data[0], $data[1]);
            }
            if (isset($data['fromClass'])) {
                $uriVariables[$parameterName]['from_class'] = $data['fromClass'];
            }
            if (isset($data['fromProperty'])) {
                $uriVariables[$parameterName]['from_property'] = $data['fromProperty'];
            }
            if (isset($data['toClass'])) {
                $uriVariables[$parameterName]['to_class'] = $data['toClass'];
            }
            if (isset($data['toProperty'])) {
                $uriVariables[$parameterName]['to_property'] = $data['toProperty'];
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

                $collection = $this->phpize($operation, 'collection', 'bool', false);
                if (Query::class === $class && $collection) {
                    $class = QueryCollection::class;
                }

                $delete = $this->phpize($operation, 'delete', 'bool', false);
                if (Mutation::class === $class && $delete) {
                    $class = DeleteMutation::class;
                }

                $data[] = array_merge($datum, [
                    'graphql_operation_class' => $class,
                    'resolver' => $this->phpize($operation, 'resolver', 'string'),
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
}
