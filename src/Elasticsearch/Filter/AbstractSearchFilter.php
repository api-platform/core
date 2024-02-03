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

namespace ApiPlatform\Elasticsearch\Filter;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Abstract class with helpers for easing the implementation of a search filter like a term filter or a match filter.
 *
 * @experimental
 *
 * @internal
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class AbstractSearchFilter extends AbstractFilter implements ConstantScoreFilterInterface
{
    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, protected IriConverterInterface $iriConverter, protected PropertyAccessorInterface $propertyAccessor, ?NameConverterInterface $nameConverter = null, ?array $properties = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $resourceClassResolver, $nameConverter, $properties);
    }

    public function apply(array $clauseBody, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $searches = [];

        foreach ($context['filters'] ?? [] as $property => $values) {
            [$type, $hasAssociation, $nestedResourceClass, $nestedProperty] = $this->getMetadata($resourceClass, $property);

            if (!$type || !$values = (array) $values) {
                continue;
            }

            if ($hasAssociation || $this->isIdentifier($nestedResourceClass, $nestedProperty, $operation)) {
                $values = array_map($this->getIdentifierValue(...), $values, array_fill(0, \count($values), $nestedProperty));
            }

            if (!$this->hasValidValues($values, $type)) {
                continue;
            }

            $property = null === $this->nameConverter ? $property : $this->nameConverter->normalize($property, $resourceClass, null, $context);
            $nestedPath = $this->getNestedFieldPath($resourceClass, $property);
            $nestedPath = null === $nestedPath || null === $this->nameConverter ? $nestedPath : $this->nameConverter->normalize($nestedPath, $resourceClass, null, $context);

            $searches[] = $this->getQuery($property, $values, $nestedPath);
        }

        if (!$searches) {
            return $clauseBody;
        }

        return array_merge_recursive($clauseBody, [
            'bool' => [
                'must' => $searches,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->getProperties($resourceClass) as $property) {
            [$type, $hasAssociation] = $this->getMetadata($resourceClass, $property);

            if (!$type) {
                continue;
            }

            foreach ([$property, "{$property}[]"] as $filterParameterName) {
                $description[$filterParameterName] = [
                    'property' => $property,
                    'type' => $hasAssociation ? 'string' : $this->getPhpType($type),
                    'required' => false,
                    'is_collection' => str_ends_with((string) $filterParameterName, '[]'),
                ];
            }
        }

        return $description;
    }

    /**
     * Gets the Elasticsearch query corresponding to the current search filter.
     */
    abstract protected function getQuery(string $property, array $values, ?string $nestedPath): array;

    /**
     * Converts the given {@see Type} in PHP type.
     */
    protected function getPhpType(Type $type): string
    {
        switch ($builtinType = $type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_ARRAY:
            case Type::BUILTIN_TYPE_INT:
            case Type::BUILTIN_TYPE_FLOAT:
            case Type::BUILTIN_TYPE_BOOL:
            case Type::BUILTIN_TYPE_STRING:
                return $builtinType;
            case Type::BUILTIN_TYPE_OBJECT:
                if (null !== ($className = $type->getClassName()) && is_a($className, \DateTimeInterface::class, true)) {
                    return \DateTimeInterface::class;
                }

                // no break
            default:
                return 'string';
        }
    }

    /**
     * Is the given property of the given resource class an identifier?
     */
    protected function isIdentifier(string $resourceClass, string $property, ?Operation $operation = null): bool
    {
        $identifier = 'id';
        if ($operation instanceof HttpOperation) {
            $uriVariable = $operation->getUriVariables()[0] ?? null;

            if ($uriVariable) {
                $identifier = $uriVariable->getIdentifiers()[0] ?? 'id';
            }
        }

        return $property === $identifier;
    }

    /**
     * Gets the ID from an IRI or a raw ID.
     */
    protected function getIdentifierValue(string $iri, string $property): mixed
    {
        try {
            $item = $this->iriConverter->getResourceFromIri($iri, ['fetch_data' => false]);

            return $this->propertyAccessor->getValue($item, $property);
        } catch (InvalidArgumentException) {
        }

        return $iri;
    }

    /**
     * Are the given values valid according to the given {@see Type}?
     */
    protected function hasValidValues(array $values, Type $type): bool
    {
        foreach ($values as $value) {
            if (
                null !== $value
                && Type::BUILTIN_TYPE_INT === $type->getBuiltinType()
                && false === filter_var($value, \FILTER_VALIDATE_INT)
            ) {
                return false;
            }
        }

        return true;
    }
}
