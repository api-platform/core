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

namespace ApiPlatform\Core\Metadata\Resource;

/**
 * Resource metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ResourceMetadata implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @var OperationCollectionMetadata[]
     */
    private $operations;

    public function __construct(array $operations)
    {
        $this->operations = $operations;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->operations);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->operations);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): OperationCollectionMetadata
    {
        return $this->operations[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->operations[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        unset($this->operations[$offset]);
    }

    /**
     * Gets the path.
     */
    public function getPath(): ?string
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getPath();
    }

    /**
     * Returns a new instance with the given path.
     */
    public function withPath(string $path): OperationCollectionMetadata
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->withPath($path);
    }

    /**
     * Gets the short name.
     */
    public function getShortName(): ?string
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getShortName();
    }

    /**
     * Returns a new instance with the given short name.
     */
    public function withShortName(string $shortName): OperationCollectionMetadata
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->withShortName($shortName);
    }

    /**
     * Gets the description.
     */
    public function getDescription(): ?string
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getDescription();
    }

    /**
     * Returns a new instance with the given description.
     */
    public function withDescription(string $description): OperationCollectionMetadata
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->withDescription($description);
    }

    /**
     * Gets the associated IRI.
     */
    public function getIri(): ?string
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getIri();
    }

    /**
     * Returns a new instance with the given IRI.
     */
    public function withIri(string $iri): OperationCollectionMetadata
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->withIri($iri);
    }

    /**
     * Gets item operations.
     */
    public function getItemOperations(): ?array
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getItemOperations();
    }

    /**
     * Returns a new instance with the given item operations.
     */
    public function withItemOperations(array $itemOperations): OperationCollectionMetadata
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->withItemOperations($itemOperations);
    }

    /**
     * Gets collection operations.
     */
    public function getCollectionOperations(): ?array
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getCollectionOperations();
    }

    /**
     * Returns a new instance with the given collection operations.
     */
    public function withCollectionOperations(array $collectionOperations): OperationCollectionMetadata
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->withCollectionOperations($collectionOperations);
    }

    /**
     * Gets subresource operations.
     */
    public function getSubresourceOperations(): ?array
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getSubresourceOperations();
    }

    /**
     * Returns a new instance with the given subresource operations.
     */
    public function withSubresourceOperations(array $subresourceOperations): OperationCollectionMetadata
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->withSubresourceOperations($subresourceOperations);
    }

    /**
     * Gets a collection operation attribute, optionally fallback to a resource attribute.
     *
     * @param mixed|null $defaultValue
     */
    public function getCollectionOperationAttribute(?string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getCollectionOperationAttribute($operationName, $key, $defaultValue, $resourceFallback);
    }

    /**
     * Gets an item operation attribute, optionally fallback to a resource attribute.
     *
     * @param mixed|null $defaultValue
     */
    public function getItemOperationAttribute(?string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getItemOperationAttribute($operationName, $key, $defaultValue, $resourceFallback);
    }

    /**
     * Gets a subresource operation attribute, optionally fallback to a resource attribute.
     *
     * @param mixed|null $defaultValue
     */
    public function getSubresourceOperationAttribute(?string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getSubresourceOperationAttribute($operationName, $key, $defaultValue, $resourceFallback);
    }

    public function getGraphqlAttribute(string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getGraphqlAttribute($operationName, $key, $defaultValue, $resourceFallback);
    }

    /**
     * Gets the first available operation attribute according to the following order: collection, item, subresource, optionally fallback to a default value.
     *
     * @param mixed|null $defaultValue
     */
    public function getOperationAttribute(array $attributes, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getOperationAttribute($attributes, $key, $defaultValue, $resourceFallback);
    }

    /**
     * Gets an attribute for a given operation type and operation name.
     *
     * @param mixed|null $defaultValue
     */
    public function getTypedOperationAttribute(string $operationType, string $operationName, string $key, $defaultValue = null, bool $resourceFallback = false)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getTypedOperationAttribute($operationType, $operationName, $key, $defaultValue, $resourceFallback);
    }

    /**
     * Gets attributes.
     */
    public function getAttributes(): ?array
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getAttributes();
    }

    /**
     * Gets an attribute.
     *
     * @param mixed|null $defaultValue
     */
    public function getAttribute(string $key, $defaultValue = null)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getAttribute($key, $defaultValue);
    }

    /**
     * Returns a new instance with the given attribute.
     */
    public function withAttributes(array $attributes): OperationCollectionMetadata
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->withAttributes($attributes);
    }

    /**
     * Gets options of for the GraphQL query.
     */
    public function getGraphql(): ?array
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->getGraphql();
    }

    /**
     * Returns a new instance with the given GraphQL options.
     */
    public function withGraphql(array $graphql): OperationCollectionMetadata
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 2.7 and will be removed in 3.0, prefer parsing '.__CLASS__.' collection of OperationCollectionMetadata objects.', E_USER_DEPRECATED);

        return $this->getFirstOperation()->withGraphql($graphql);
    }

    private function getFirstOperation(): ?OperationCollectionMetadata
    {
        return reset($this->operations) ?: null;
    }
}
