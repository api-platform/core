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

namespace ApiPlatform\Api;

use ApiPlatform\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * UriVariables converter that chains uri variables transformers.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class UriVariablesConverter implements UriVariablesConverterInterface
{
    /**
     * @param iterable<UriVariableTransformerInterface> $uriVariableTransformers
     */
    public function __construct(private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly iterable $uriVariableTransformers)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function convert(array $uriVariables, string $class, array $context = []): array
    {
        $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($class)->getOperation();
        $context += ['operation' => $operation];
        $uriVariablesDefinitions = $operation->getUriVariables() ?? [];

        foreach ($uriVariables as $parameterName => $value) {
            $uriVariableDefinition = $uriVariablesDefinitions[$parameterName] ?? $uriVariablesDefinitions['id'] ?? new Link();
            if ([] === $types = $this->getIdentifierTypes($uriVariableDefinition->getFromClass() ?? $class, $uriVariableDefinition->getIdentifiers() ?? [$parameterName])) {
                continue;
            }

            foreach ($this->uriVariableTransformers as $uriVariableTransformer) {
                if (!$uriVariableTransformer->supportsTransformation($value, $types, $context)) {
                    continue;
                }

                try {
                    $uriVariables[$parameterName] = $uriVariableTransformer->transform($value, $types, $context);
                    break;
                } catch (InvalidUriVariableException $e) {
                    throw new InvalidUriVariableException(sprintf('Identifier "%s" could not be transformed.', $parameterName), $e->getCode(), $e);
                }
            }
        }

        return $uriVariables;
    }

    private function getIdentifierTypes(string $resourceClass, array $properties): array
    {
        $types = [];
        foreach ($properties as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);
            foreach ($propertyMetadata->getBuiltinTypes() as $type) {
                $types[] = Type::BUILTIN_TYPE_OBJECT === ($builtinType = $type->getBuiltinType()) ? $type->getClassName() : $builtinType;
            }
        }

        return $types;
    }
}
