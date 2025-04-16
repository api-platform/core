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

namespace ApiPlatform\Metadata;

use ApiPlatform\Metadata\Exception\InvalidUriVariableException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\TypeInfo\Type\CompositeTypeInterface;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;

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
     *
     * To handle the composite identifiers type correctly, use an `uri_variables_map` that maps uriVariables to their uriVariablesDefinition.
     * Indeed, a composite identifier will already be parsed, and their corresponding properties will be the parameterName and not the defined
     * identifiers.
     */
    public function convert(array $uriVariables, string $class, array $context = []): array
    {
        $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($class)->getOperation();
        $context += ['operation' => $operation];
        $uriVariablesDefinitions = $operation->getUriVariables() ?? [];

        foreach ($uriVariables as $parameterName => $value) {
            $uriVariableDefinition = $context['uri_variables_map'][$parameterName] ?? $uriVariablesDefinitions[$parameterName] ?? $uriVariablesDefinitions['id'] ?? new Link();

            // When a composite identifier is used, we assume that the parameterName is the property to find our type
            $properties = $uriVariableDefinition->getIdentifiers() ?? [$parameterName];
            if ($uriVariableDefinition->getCompositeIdentifier()) {
                $properties = [$parameterName];
            }

            if (!$types = $this->getIdentifierTypeStrings($uriVariableDefinition->getFromClass() ?? $class, $properties)) {
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
                    throw new InvalidUriVariableException(\sprintf('Identifier "%s" could not be transformed.', $parameterName), $e->getCode(), $e);
                }
            }
        }

        return $uriVariables;
    }

    /**
     * @return list<string>
     */
    private function getIdentifierTypeStrings(string $resourceClass, array $properties): array
    {
        $typeStrings = [];

        foreach ($properties as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);

            if (!$type = $propertyMetadata->getNativeType()) {
                continue;
            }

            foreach ($type->traverse() as $t) {
                if (!$t instanceof CompositeTypeInterface && !$t instanceof WrappingTypeInterface) {
                    $typeStrings[] = (string) $t;
                }
            }
        }

        return $typeStrings;
    }
}
