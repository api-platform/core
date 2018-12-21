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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Filter the collection by given properties.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class TermFilter extends AbstractFilter implements ConstantScoreFilterInterface
{
    private $identifierExtractor;
    private $iriConverter;
    private $propertyAccessor;

    /**
     * {@inheritdoc}
     */
    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, IdentifierExtractorInterface $identifierExtractor, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor, ?NameConverterInterface $nameConverter = null, ?array $properties = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $resourceClassResolver, $nameConverter, $properties);

        $this->identifierExtractor = $identifierExtractor;
        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(array $clauseBody, string $resourceClass, ?string $operationName = null, array $context = null): array
    {
        $terms = [];

        foreach ($context['filters'] ?? [] as $property => $values) {
            [$type, $hasAssociation, $nestedResourceClass, $nestedProperty] = $this->getMetadata($resourceClass, $property);

            if (!$type || !$values = (array) $values) {
                continue;
            }

            if ($hasAssociation || $this->isIdentifier($nestedResourceClass, $nestedProperty)) {
                $values = array_map([$this, 'getIdentifierValue'], $values, array_fill(0, \count($values), $nestedProperty));
            }

            if (!$this->hasValidValues($values, $type)) {
                continue;
            }

            $convertedProperty = null === $this->nameConverter ? $property : $this->nameConverter->normalize($property);

            if (1 === \count($values)) {
                $term = ['term' => [$convertedProperty => reset($values)]];
            } else {
                $term = ['terms' => [$convertedProperty => $values]];
            }

            if (null !== $nestedPath = $this->getNestedFieldPath($resourceClass, $property)) {
                $nestedPath = null === $this->nameConverter ? $nestedPath : $this->nameConverter->normalize($nestedPath);
                $term = ['nested' => ['path' => $nestedPath, 'query' => $term]];
            }

            $terms[] = $term;
        }

        if (!$terms) {
            return $clauseBody;
        }

        return array_merge_recursive($clauseBody, [
            'bool' => [
                'must' => $terms,
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

            foreach ([$property, "${property}[]"] as $filterParameterName) {
                $description[$filterParameterName] = [
                    'property' => $property,
                    'type' => $hasAssociation ? 'string' : $this->getPHPType($type),
                    'required' => false,
                ];
            }
        }

        return $description;
    }

    /**
     * Converts the given {@see Type} in PHP type.
     */
    private function getPHPType(Type $type): string
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
    private function isIdentifier(string $resourceClass, string $property): bool
    {
        return $property === $this->identifierExtractor->getIdentifierFromResourceClass($resourceClass);
    }

    /**
     * Gets the ID from an IRI or a raw ID.
     */
    private function getIdentifierValue(string $iri, string $property)
    {
        try {
            if ($item = $this->iriConverter->getItemFromIri($iri, ['fetch_data' => false])) {
                return $this->propertyAccessor->getValue($item, $property);
            }
        } catch (InvalidArgumentException $e) {
        }

        return $iri;
    }

    /**
     * Are the given values valid according to the given {@see Type}?
     */
    private function hasValidValues(array $values, Type $type): bool
    {
        foreach ($values as $value) {
            if (
                null !== $value
                && Type::BUILTIN_TYPE_INT === $type->getBuiltinType()
                && false === filter_var($value, FILTER_VALIDATE_INT)
            ) {
                return false;
            }
        }

        return true;
    }
}
