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

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;

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
    private $identifiersExtractor;
    private $iriConverter;
    private $propertyAccessor;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, IdentifiersExtractorInterface $identifiersExtractor, IriConverterInterface $iriConverter, PropertyAccessorInterface $propertyAccessor, array $properties = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $resourceClassResolver, $properties);

        $this->identifiersExtractor = $identifiersExtractor;
        $this->iriConverter = $iriConverter;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(array &$clauseBody, string $resourceClass, string $operationName = null, array $context): void
    {
        $terms = [];

        foreach ($context['filters'] ?? [] as $property => $values) {
            list($type, $hasAssociation, $nestedResourceClass, $nestedProperty) = $this->getMetadata($resourceClass, $property);

            if (!$type || !$values = (array) $values) {
                continue;
            }

            if ($hasAssociation || $this->isIdentifier($nestedResourceClass, $nestedProperty)) {
                $values = array_map([$this, 'getIdentifierValue'], $values, array_fill(0, \count($values), $nestedProperty));
            }

            if (!$this->hasValidValues($values, $type)) {
                continue;
            }

            if (1 === \count($values)) {
                $term = ['term' => [$property => $values[0]]];
            } else {
                $term = ['terms' => [$property => $values]];
            }

            if ($this->isNestedField($resourceClass, $nestedPath = explode('.', $property)[0])) {
                $term = ['nested' => ['path' => $nestedPath, 'query' => $term]];
            }

            $terms[] = $term;
        }

        if (!$terms) {
            return;
        }

        $clauseBody = array_merge_recursive($clauseBody, [
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
            list($type, $hasAssociation) = $this->getMetadata($resourceClass, $property);

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
        if (null === $builtinType = $type->getBuiltinType()) {
            return 'string';
        }

        switch ($builtinType) {
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
        return \in_array($property, $this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass), true);
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
                Type::BUILTIN_TYPE_INT === $type->getBuiltinType()
                && null !== $value
                && false === filter_var($value, FILTER_VALIDATE_INT)
            ) {
                return false;
            }
        }

        return true;
    }
}
