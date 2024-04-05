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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\FilterInterface;
use ApiPlatform\Metadata\HeaderParameterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use ApiPlatform\Serializer\Filter\FilterInterface as SerializerFilterInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\DivisibleBy;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Unique;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Prepares Parameters documentation by reading its filter details and declaring an OpenApi parameter.
 *
 * @experimental
 */
final class ParameterResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null, private readonly ?ContainerInterface $filterLocator = null)
    {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated?->create($resourceClass) ?? new ResourceMetadataCollection($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = $resource->getOperations();

            $internalPriority = -1;
            foreach ($operations as $operationName => $operation) {
                $parameters = [];
                foreach ($operation->getParameters() ?? [] as $key => $parameter) {
                    $parameter = $this->setDefaults($key, $parameter, $resourceClass);
                    $priority = $parameter->getPriority() ?? $internalPriority--;
                    $parameters[$key] = $parameter->withPriority($priority);
                }

                $operations->add($operationName, $operation->withParameters(new Parameters($parameters)));
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations->sort());

            $internalPriority = -1;
            $graphQlOperations = $resource->getGraphQlOperations();
            foreach ($graphQlOperations ?? [] as $operationName => $operation) {
                $parameters = [];
                foreach ($operation->getParameters() ?? [] as $key => $parameter) {
                    $parameter = $this->setDefaults($key, $parameter, $resourceClass);
                    $priority = $parameter->getPriority() ?? $internalPriority--;
                    $parameters[$key] = $parameter->withPriority($priority);
                }

                $graphQlOperations[$operationName] = $operation->withParameters(new Parameters($parameters));
            }

            if ($graphQlOperations) {
                $resourceMetadataCollection[$i] = $resource->withGraphQlOperations($graphQlOperations);
            }
        }

        return $resourceMetadataCollection;
    }

    private function setDefaults(string $key, Parameter $parameter, string $resourceClass): Parameter
    {
        if (null === $parameter->getKey()) {
            $parameter = $parameter->withKey($key);
        }

        $filter = $parameter->getFilter();
        if (\is_string($filter) && $this->filterLocator->has($filter)) {
            $filter = $this->filterLocator->get($filter);
        }

        if ($filter instanceof SerializerFilterInterface && null === $parameter->getProvider()) {
            $parameter = $parameter->withProvider('api_platform.serializer.filter_parameter_provider');
        }

        // Read filter description to populate the Parameter
        $description = $filter instanceof FilterInterface ? $filter->getDescription($resourceClass) : [];
        if (($schema = $description[$key]['schema'] ?? null) && null === $parameter->getSchema()) {
            $parameter = $parameter->withSchema($schema);
        }

        if (null === $parameter->getProperty() && ($property = $description[$key]['property'] ?? null)) {
            $parameter = $parameter->withProperty($property);
        }

        if (null === $parameter->getRequired() && ($required = $description[$key]['required'] ?? null)) {
            $parameter = $parameter->withRequired($required);
        }

        if (null === $parameter->getOpenApi() && $openApi = $description[$key]['openapi'] ?? null) {
            if ($openApi instanceof OpenApiParameter) {
                $parameter = $parameter->withOpenApi($openApi);
            } elseif (\is_array($openApi)) {
                // @phpstan-ignore-next-line
                $schema = $schema ?? $openapi['schema'] ?? [];
                $parameter = $parameter->withOpenApi(new OpenApiParameter(
                    $key,
                    $parameter instanceof HeaderParameterInterface ? 'header' : 'query',
                    $description[$key]['description'] ?? '',
                    $description[$key]['required'] ?? $openApi['required'] ?? false,
                    $openApi['deprecated'] ?? false,
                    $openApi['allowEmptyValue'] ?? true,
                    $schema,
                    $openApi['style'] ?? null,
                    $openApi['explode'] ?? ('array' === ($schema['type'] ?? null)),
                    $openApi['allowReserved'] ?? false,
                    $openApi['example'] ?? null,
                    isset(
                        $openApi['examples']
                    ) ? new \ArrayObject($openApi['examples']) : null
                ));
            }
        }

        $schema = $parameter->getSchema() ?? $parameter->getOpenApi()?->getSchema();

        // Only add validation if the Symfony Validator is installed
        if (interface_exists(ValidatorInterface::class) && !$parameter->getConstraints()) {
            $parameter = $this->addSchemaValidation($parameter, $schema, $parameter->getRequired() ?? $description['required'] ?? false, $parameter->getOpenApi());
        }

        return $parameter;
    }

    private function addSchemaValidation(Parameter $parameter, ?array $schema = null, bool $required = false, ?OpenApiParameter $openApi = null): Parameter
    {
        $assertions = [];

        if ($required) {
            $assertions[] = new NotNull(message: sprintf('The parameter "%s" is required.', $parameter->getKey()));
        }

        if (isset($schema['exclusiveMinimum'])) {
            $assertions[] = new GreaterThan(value: $schema['exclusiveMinimum']);
        }

        if (isset($schema['exclusiveMaximum'])) {
            $assertions[] = new LessThan(value: $schema['exclusiveMaximum']);
        }

        if (isset($schema['minimum'])) {
            $assertions[] = new GreaterThanOrEqual(value: $schema['minimum']);
        }

        if (isset($schema['maximum'])) {
            $assertions[] = new LessThanOrEqual(value: $schema['maximum']);
        }

        if (isset($schema['pattern'])) {
            $assertions[] = new Regex($schema['pattern']);
        }

        if (isset($schema['maxLength']) || isset($schema['minLength'])) {
            $assertions[] = new Length(min: $schema['minLength'] ?? null, max: $schema['maxLength'] ?? null);
        }

        if (isset($schema['minItems']) || isset($schema['maxItems'])) {
            $assertions[] = new Count(min: $schema['minItems'] ?? null, max: $schema['maxItems'] ?? null);
        }

        if (isset($schema['multipleOf'])) {
            $assertions[] = new DivisibleBy(value: $schema['multipleOf']);
        }

        if ($schema['uniqueItems'] ?? false) {
            $assertions[] = new Unique();
        }

        if (isset($schema['enum'])) {
            $assertions[] = new Choice(choices: $schema['enum']);
        }

        if (false === $openApi?->getAllowEmptyValue()) {
            $assertions[] = new NotBlank(allowNull: !$required);
        }

        if (!$assertions) {
            return $parameter;
        }

        if (1 === \count($assertions)) {
            return $parameter->withConstraints($assertions[0]);
        }

        return $parameter->withConstraints($assertions);
    }
}
