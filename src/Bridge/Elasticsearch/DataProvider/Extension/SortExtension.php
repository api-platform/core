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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Util\FieldDatatypeTrait;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;

/**
 * Applies selected sorting while querying resource collection.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/guide/current/_sorting.html
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-sort.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class SortExtension implements FullBodySearchCollectionExtensionInterface
{
    use FieldDatatypeTrait;

    private $defaultDirection;
    private $identifiersExtractor;
    private $resourceMetadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, IdentifiersExtractorInterface $identifiersExtractor, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, string $defaultDirection = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->identifiersExtractor = $identifiersExtractor;
        $this->defaultDirection = $defaultDirection;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(array &$requestBody, string $resourceClass, string $operationName = null, array $context): void
    {
        $orders = [];

        if (null !== ($defaultOrder = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('order')) && \is_array($defaultOrder)) {
            foreach ($defaultOrder as $property => $direction) {
                if (\is_int($property)) {
                    $property = $direction;
                    $direction = 'asc';
                }

                $orders[] = $this->getOrder($resourceClass, $property, $direction);
            }
        } elseif (null !== $this->defaultDirection) {
            foreach ($this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass) as $identifier) {
                $orders[] = $this->getOrder($resourceClass, $identifier, $this->defaultDirection);
            }
        }

        if (!$orders) {
            return;
        }

        $requestBody['sort'] = array_merge_recursive($requestBody['sort'] ?? [], $orders);
    }

    private function getOrder(string $resourceClass, string $property, string $direction): array
    {
        $order = ['order' => strtolower($direction)];

        if ($this->isNestedField($resourceClass, $nestedPath = explode('.', $property)[0])) {
            $order['nested_path'] = $nestedPath;
        }

        return [$property => $order];
    }
}
