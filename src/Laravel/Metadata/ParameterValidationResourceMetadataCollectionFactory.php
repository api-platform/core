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

namespace ApiPlatform\Laravel\Metadata;

use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Illuminate\Validation\Rule;
use Psr\Container\ContainerInterface;

final class ParameterValidationResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null,
        private readonly ?ContainerInterface $filterLocator = null,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated?->create($resourceClass) ?? new ResourceMetadataCollection($resourceClass);

        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = $resource->getOperations();

            foreach ($operations as $operationName => $operation) {
                $parameters = $operation->getParameters() ?? new Parameters();
                foreach ($parameters as $key => $parameter) {
                    $parameters->add($key, $this->addSchemaValidation($parameter));
                }

                if (\count($parameters) > 0) {
                    $operations->add($operationName, $operation->withParameters($parameters));
                }
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations->sort());

            if (!$graphQlOperations = $resource->getGraphQlOperations()) {
                continue;
            }

            foreach ($graphQlOperations as $operationName => $operation) {
                $parameters = $operation->getParameters() ?? new Parameters();
                foreach ($operation->getParameters() ?? [] as $key => $parameter) {
                    $parameters->add($key, $this->addSchemaValidation($parameter));
                }

                if (\count($parameters) > 0) {
                    $graphQlOperations[$operationName] = $operation->withParameters($parameters);
                }
            }

            $resourceMetadataCollection[$i] = $resource->withGraphQlOperations($graphQlOperations);
        }

        return $resourceMetadataCollection;
    }

    private function addSchemaValidation(Parameter $parameter): Parameter
    {
        $schema = $parameter->getSchema();
        $required = $parameter->getRequired();

        $openApi = $parameter->getOpenApi();

        // When it's an array of openapi parameters take the first one as it's probably just a variant of the query parameter,
        // only getAllowEmptyValue is used here anyways
        if (\is_array($openApi)) {
            $openApi = $openApi[0];
        }
        $assertions = [];
        $allowEmptyValue = $openApi?->getAllowEmptyValue();
        if ($required || (false === $required && false === $allowEmptyValue)) {
            $assertions[] = 'required';
        }

        if (true === $allowEmptyValue) {
            $assertions[] = 'nullable';
        }

        if (isset($schema['exclusiveMinimum'])) {
            $assertions[] = 'gt:'.$schema['exclusiveMinimum'];
        }

        if (isset($schema['exclusiveMaximum'])) {
            $assertions[] = 'lt:'.$schema['exclusiveMaximum'];
        }

        if (isset($schema['minimum'])) {
            $assertions[] = 'gte:'.$schema['minimum'];
        }

        if (isset($schema['maximum'])) {
            $assertions[] = 'lte:'.$schema['maximum'];
        }

        if (isset($schema['pattern'])) {
            $assertions[] = 'regex:'.$schema['pattern'];
        }

        $minLength = isset($schema['minLength']);
        $maxLength = isset($schema['maxLength']);

        if ($minLength && $maxLength) {
            $assertions[] = sprintf('between:%s,%s', $schema['minLength'], $schema['maxLength']);
        } elseif ($minLength) {
            $assertions[] = 'min:'.$schema['minLength'];
        } elseif ($maxLength) {
            $assertions[] = 'max:'.$schema['maxLength'];
        }

        $minItems = isset($schema['minItems']);
        $maxItems = isset($schema['maxItems']);

        if ($minItems && $maxItems) {
            $assertions[] = sprintf('between:%s,%s', $schema['minItems'], $schema['maxItems']);
        } elseif ($minItems) {
            $assertions[] = 'min:'.$schema['minItems'];
        } elseif ($maxItems) {
            $assertions[] = 'max:'.$schema['maxItems'];
        }

        if (isset($schema['multipleOf'])) {
            $assertions[] = 'multiple_of:'.$schema['multipleOf'];
        }

        if (isset($schema['enum'])) {
            $assertions[] = Rule::in($schema['enum']);
        }

        if (isset($schema['type']) && 'array' === $schema['type']) {
            $assertions[] = 'array';
        }

        if (isset($schema['type']) && 'boolean' === $schema['type']) {
            $assertions[] = 'boolean';
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
