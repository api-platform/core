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

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;

/**
 * Order the collection by given properties.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/guide/current/_sorting.html
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class OrderFilter extends AbstractFilter implements SortFilterInterface
{
    private $orderParameterName;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, string $orderParameterName = 'order', array $properties = null)
    {
        parent::__construct($propertyNameCollectionFactory, $propertyMetadataFactory, $resourceClassResolver, $properties);

        $this->orderParameterName = $orderParameterName;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(array &$clauseBody, string $resourceClass, string $operationName = null, array $context): void
    {
        if (!\is_array($properties = $context['filters'][$this->orderParameterName] ?? [])) {
            return;
        }

        $orders = [];

        foreach ($properties as $property => $direction) {
            list($type) = $this->getMetadata($resourceClass, $property);

            if (!$type) {
                continue;
            }

            if (empty($direction) && null !== $defaultDirection = $this->properties[$property]['default_direction'] ?? null) {
                $direction = $defaultDirection;
            }

            if (!\in_array($direction = strtolower($direction), ['asc', 'desc'], true)) {
                continue;
            }

            $order = ['order' => $direction];

            if ($this->isNestedField($resourceClass, $nestedPath = explode('.', $property)[0])) {
                $order['nested_path'] = $nestedPath;
            }

            $orders[] = [$property => $order];
        }

        if (!$orders) {
            return;
        }

        $clauseBody = array_merge_recursive($clauseBody, $orders);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        foreach ($this->getProperties($resourceClass) as $property) {
            list($type) = $this->getMetadata($resourceClass, $property);

            if (!$type) {
                continue;
            }

            $description["$this->orderParameterName[$property]"] = [
                'property' => $property,
                'type' => 'string',
                'required' => false,
            ];
        }

        return $description;
    }
}
