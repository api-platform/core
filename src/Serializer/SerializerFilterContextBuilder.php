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

namespace ApiPlatform\Serializer;

use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Serializer\Filter\FilterInterface;
use ApiPlatform\Util\RequestAttributesExtractor;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class SerializerFilterContextBuilder implements SerializerContextBuilderInterface
{
    private $decorated;
    private $filterLocator;
    private $resourceMetadataCollectionFactory;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ContainerInterface $filterLocator, SerializerContextBuilderInterface $decorated)
    {
        $this->decorated = $decorated;
        $this->filterLocator = $filterLocator;
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, array $attributes = null): array
    {
        if (null === $attributes && !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            throw new RuntimeException('Request attributes are not valid.');
        }

        $context = $this->decorated->createFromRequest($request, $normalization, $attributes);

        $resourceFilters = $this->resourceMetadataCollectionFactory->create($attributes['resource_class'])->getOperation($attributes['operation_name'] ?? null)->getFilters();

        if (!$resourceFilters) {
            return $context;
        }

        foreach ($resourceFilters as $filterId) {
            if ($this->filterLocator->has($filterId) && ($filter = $this->filterLocator->get($filterId)) instanceof FilterInterface) {
                $filter->apply($request, $normalization, $attributes, $context);
            }
        }

        return $context;
    }
}
