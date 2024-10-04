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

use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\ExternalDocumentation;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\State\OptionsInterface;
use Symfony\Component\WebLink\Link;
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
    protected function extractPath(string $path): void
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
            throw new InvalidArgumentException(\sprintf('"resources" setting is expected to be null or an array, %s given in "%s".', \gettype($resourcesYaml), $path));
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
                    throw new InvalidArgumentException(\sprintf('%s in "%s" (%s).', $exception->getMessage(), $resourceName, $path));
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
            'queryParameterValidationEnabled' => $this->phpize($resource, 'queryParameterValidationEnabled', 'bool'),
            'types' => $this->buildArrayValue($resource, 'types'),
            'cacheHeaders' => $this->buildArrayValue($resource, 'cacheHeaders'),
            'hydraContext' => $this->buildArrayValue($resource, 'hydraContext'),
            'openapi' => $this->buildOpenapi($resource),
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
            'stateOptions' => $this->buildStateOptions($resource),
            'links' => $this->buildLinks($resource),
            'headers' => $this->buildHeaders($resource),
            'parameters' => $this->buildParameters($resource),
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
            'processor' => $this->phpize($resource, 'processor', 'string'),
            'provider' => $this->phpize($resource, 'provider', 'string'),
            'security' => $this->phpize($resource, 'security', 'string'),
            'securityMessage' => $this->phpize($resource, 'securityMessage', 'string'),
            'securityPostDenormalize' => $this->phpize($resource, 'securityPostDenormalize', 'string'),
            'securityPostDenormalizeMessage' => $this->phpize($resource, 'securityPostDenormalizeMessage', 'string'),
            'securityPostValidation' => $this->phpize($resource, 'securityPostValidation', 'string'),
            'securityPostValidationMessage' => $this->phpize($resource, 'securityPostValidationMessage', 'string'),
            'input' => $this->phpize($resource, 'input', 'bool|string'),
            'output' => $this->phpize($resource, 'output', 'bool|string'),
            'normalizationContext' => $this->buildArrayValue($resource, 'normalizationContext'),
            'denormalizationContext' => $this->buildArrayValue($resource, 'denormalizationContext'),
            'collectDenormalizationErrors' => $this->phpize($resource, 'collectDenormalizationErrors', 'bool'),
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
                $uriVariables[$parameterName]['from_class'] = $this->resolve($data['fromClass']);
            }
            if (isset($data['fromProperty'])) {
                $uriVariables[$parameterName]['from_property'] = $data['fromProperty'];
            }
            if (isset($data['toClass'])) {
                $uriVariables[$parameterName]['to_class'] = $this->resolve($data['toClass']);
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

    private function buildOpenapi(array $resource): bool|OpenApiOperation|null
    {
        if (!\array_key_exists('openapi', $resource)) {
            return null;
        }

        if (!\is_array($resource['openapi'])) {
            return $this->phpize($resource, 'openapi', 'bool');
        }

        $allowedProperties = array_map(fn (\ReflectionProperty $reflProperty): string => $reflProperty->getName(), (new \ReflectionClass(OpenApiOperation::class))->getProperties());
        foreach ($resource['openapi'] as $key => $value) {
            $resource['openapi'][$key] = match ($key) {
                'externalDocs' => new ExternalDocumentation(description: $value['description'] ?? '', url: $value['url'] ?? ''),
                'requestBody' => new RequestBody(description: $value['description'] ?? '', content: isset($value['content']) ? new \ArrayObject($value['content'] ?? []) : null, required: $value['required'] ?? false),
                'callbacks' => new \ArrayObject($value ?? []),
                default => $value,
            };

            if (\in_array($key, $allowedProperties, true)) {
                continue;
            }

            $resource['openapi']['extensionProperties'][$key] = $value;
            unset($resource['openapi'][$key]);
        }

        if (\array_key_exists('parameters', $resource['openapi']) && \is_array($openapiParameters = $resource['openapi']['parameters'] ?? [])) {
            $parameters = [];
            foreach ($openapiParameters as $parameter) {
                $parameters[] = new Parameter(
                    name: $parameter['name'],
                    in: $parameter['in'],
                    description: $parameter['description'] ?? '',
                    required: $parameter['required'] ?? false,
                    deprecated: $parameter['deprecated'] ?? false,
                    allowEmptyValue: $parameter['allowEmptyValue'] ?? false,
                    schema: $parameter['schema'] ?? [],
                    style: $parameter['style'] ?? null,
                    explode: $parameter['explode'] ?? false,
                    allowReserved: $parameter['allowReserved '] ?? false,
                    example: $parameter['example'] ?? null,
                    examples: isset($parameter['examples']) ? new \ArrayObject($parameter['examples']) : null,
                    content: isset($parameter['content']) ? new \ArrayObject($parameter['content']) : null
                );
            }
            $resource['openapi']['parameters'] = $parameters;
        }

        return new OpenApiOperation(...$resource['openapi']);
    }

    /**
     * @return bool|string|string[]|null
     */
    private function buildMercure(array $resource): array|bool|string|null
    {
        if (!\array_key_exists('mercure', $resource)) {
            return null;
        }

        if (\is_string($resource['mercure'])) {
            return $this->phpize($resource, 'mercure', 'bool|string');
        }

        return $resource['mercure'];
    }

    private function buildMessenger(array $resource): bool|array|string|null
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
                throw new InvalidArgumentException(\sprintf('Operation class "%s" does not exist', $class));
            }

            $datum = $this->buildExtendedBase($operation);
            foreach ($datum as $key => $value) {
                if (null === $value) {
                    $datum[$key] = $root[$key];
                }
            }

            if (\in_array((string) $class, [GetCollection::class, Post::class], true)) {
                $datum['itemUriTemplate'] = $this->phpize($operation, 'itemUriTemplate', 'string');
            } elseif (isset($operation['itemUriTemplate'])) {
                throw new InvalidArgumentException(\sprintf('"itemUriTemplate" option is not allowed on a %s operation.', $class));
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
        if (!\array_key_exists('graphQlOperations', $resource) || !\is_array($resource['graphQlOperations'])) {
            return null;
        }

        $data = [];
        foreach ($resource['graphQlOperations'] as $class => $operation) {
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
                throw new InvalidArgumentException(\sprintf('Operation class "%s" does not exist', $class));
            }

            $datum = $this->buildBase($operation);
            foreach ($datum as $key => $value) {
                if (null === $value) {
                    $datum[$key] = $root[$key];
                }
            }

            $data[] = array_merge($datum, [
                'resolver' => $this->phpize($operation, 'resolver', 'string'),
                'args' => $operation['args'] ?? null,
                'extraArgs' => $operation['extraArgs'] ?? null,
                'class' => (string) $class,
                'read' => $this->phpize($operation, 'read', 'bool'),
                'deserialize' => $this->phpize($operation, 'deserialize', 'bool'),
                'validate' => $this->phpize($operation, 'validate', 'bool'),
                'write' => $this->phpize($operation, 'write', 'bool'),
                'serialize' => $this->phpize($operation, 'serialize', 'bool'),
                'priority' => $this->phpize($operation, 'priority', 'integer'),
                'name' => $this->phpize($operation, 'name', 'string'),
            ]);
        }

        return $data ?: null;
    }

    private function buildStateOptions(array $resource): ?OptionsInterface
    {
        $stateOptions = $resource['stateOptions'] ?? [];
        if (!\is_array($stateOptions)) {
            return null;
        }

        if (!$stateOptions) {
            return null;
        }

        $configuration = reset($stateOptions);
        switch (key($stateOptions)) {
            case 'elasticsearchOptions':
                if (class_exists(Options::class)) {
                    return new Options($configuration['index'] ?? null, $configuration['type'] ?? null);
                }
        }

        return null;
    }

    /**
     * @return Link[]
     */
    private function buildLinks(array $resource): ?array
    {
        if (!isset($resource['links']) || !\is_array($resource['links'])) {
            return null;
        }

        $links = [];
        foreach ($resource['links'] as $link) {
            $links[] = new Link(rel: $link['rel'], href: $link['href']);
        }

        return $links;
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(array $resource): ?array
    {
        if (!isset($resource['headers']) || !\is_array($resource['headers'])) {
            return null;
        }

        $headers = [];
        foreach ($resource['headers'] as $key => $value) {
            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * @return array<string, \ApiPlatform\Metadata\Parameter>
     */
    private function buildParameters(array $resource): ?array
    {
        if (!isset($resource['parameters']) || !\is_array($resource['parameters'])) {
            return null;
        }

        $parameters = [];
        foreach ($resource['parameters'] as $key => $parameter) {
            $cl = ($parameter['in'] ?? 'query') === 'header' ? HeaderParameter::class : QueryParameter::class;
            $parameters[$key] = new $cl(
                key: $key,
                required: $this->phpize($parameter, 'required', 'bool'),
                schema: $parameter['schema'],
                openApi: ($parameter['openapi'] ?? null) ? new Parameter(
                    name: $parameter['openapi']['name'],
                    in: $parameter['in'] ?? 'query',
                    description: $parameter['openapi']['description'] ?? '',
                    required: $parameter['openapi']['required'] ?? $parameter['required'] ?? false,
                    deprecated: $parameter['openapi']['deprecated'] ?? false,
                    allowEmptyValue: $parameter['openapi']['allowEmptyValue'] ?? false,
                    schema: $parameter['openapi']['schema'] ?? $parameter['schema'] ?? [],
                    style: $parameter['openapi']['style'] ?? null,
                    explode: $parameter['openapi']['explode'] ?? false,
                    allowReserved: $parameter['openapi']['allowReserved '] ?? false,
                    example: $parameter['openapi']['example'] ?? null,
                    examples: isset($parameter['openapi']['examples']) ? new \ArrayObject($parameter['openapi']['examples']) : null,
                    content: isset($parameter['openapi']['content']) ? new \ArrayObject($parameter['openapi']['content']) : null
                ) : null,
                provider: $this->phpize($parameter, 'provider', 'string'),
                filter: $this->phpize($parameter, 'filter', 'string'),
                property: $this->phpize($parameter, 'property', 'string'),
                description: $this->phpize($parameter, 'description', 'string'),
                priority: $this->phpize($parameter, 'priority', 'integer'),
                extraProperties: $this->buildArrayValue($parameter, 'extraProperties') ?? [],
            );
        }

        return $parameters;
    }
}
