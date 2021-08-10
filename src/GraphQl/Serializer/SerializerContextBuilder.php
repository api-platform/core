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

namespace ApiPlatform\GraphQl\Serializer;

use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Builds the context used by the Symfony Serializer.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SerializerContextBuilder implements SerializerContextBuilderInterface
{
    private $resourceMetadataCollectionFactory;
    private $nameConverter;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ?NameConverterInterface $nameConverter)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->nameConverter = $nameConverter;
    }

    public function create(?string $resourceClass, string $operationName, array $resolverContext, bool $normalization): array
    {
        $context = ['resource_class' => $resourceClass, 'graphql_operation_name' => $operationName];
        $operation = null;

        if ($resourceClass) {
            $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);
            try {
                $operation = $resourceMetadata->getGraphQlOperation($operationName);
            } catch (OperationNotFoundException $e) {
                // It's possible that the serialization context may not be tight to an existing operation
            }

            try {
                $context['operation_name'] = $resourceMetadata->getOperation()->getName();
            } catch (OperationNotFoundException $e) {
            }
        }

        if (isset($resolverContext['fields'])) {
            $context['no_resolver_data'] = true;
        }

        if ($operation) {
            $context['input'] = $operation->getInput();
            $context['output'] = $operation->getOutput();
            $context = $normalization ? array_merge($operation->getNormalizationContext(), $context) : array_merge($operation->getDenormalizationContext(), $context);
        }

        if ($normalization) {
            $context['attributes'] = $this->fieldsToAttributes($resourceClass, $operation, $resolverContext, $context);
        }

        return $context;
    }

    /**
     * Retrieves fields, recursively replaces the "_id" key (the raw id) by "id" (the name of the property expected by the Serializer) and flattens edge and node structures (pagination).
     */
    private function fieldsToAttributes(?string $resourceClass, ?Operation $operation, array $resolverContext, array $context): array
    {
        if (isset($resolverContext['fields'])) {
            $fields = $resolverContext['fields'];
        } else {
            /** @var ResolveInfo $info */
            $info = $resolverContext['info'];
            $fields = $info->getFieldSelection(\PHP_INT_MAX);
        }

        $attributes = $this->replaceIdKeys($fields['edges']['node'] ?? $fields['collection'] ?? $fields, $resourceClass, $context);

        if ($resolverContext['is_mutation'] || $resolverContext['is_subscription']) {
            if (!$operation) {
                throw new \LogicException('An operation should always exist for a mutation or a subscription.');
            }

            $wrapFieldName = lcfirst($operation->getShortName());

            return $attributes[$wrapFieldName] ?? [];
        }

        return $attributes;
    }

    private function replaceIdKeys(array $fields, ?string $resourceClass, array $context): array
    {
        $denormalizedFields = [];

        foreach ($fields as $key => $value) {
            if ('_id' === $key) {
                $denormalizedFields['id'] = $fields['_id'];

                continue;
            }

            $denormalizedFields[$this->denormalizePropertyName((string) $key, $resourceClass, $context)] = \is_array($fields[$key]) ? $this->replaceIdKeys($fields[$key], $resourceClass, $context) : $value;
        }

        return $denormalizedFields;
    }

    private function denormalizePropertyName(string $property, ?string $resourceClass, array $context): string
    {
        if (null === $this->nameConverter) {
            return $property;
        }
        if ($this->nameConverter instanceof AdvancedNameConverterInterface) {
            return $this->nameConverter->denormalize($property, $resourceClass, null, $context);
        }

        return $this->nameConverter->denormalize($property);
    }
}
