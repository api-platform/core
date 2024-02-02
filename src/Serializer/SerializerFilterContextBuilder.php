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

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Serializer\Filter\FilterInterface;
use ApiPlatform\Symfony\Util\RequestAttributesExtractor;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class SerializerFilterContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly ContainerInterface $filterLocator, private readonly SerializerContextBuilderInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRequest(Request $request, bool $normalization, ?array $attributes = null): array
    {
        if (null === $attributes && !$attributes = RequestAttributesExtractor::extractAttributes($request)) {
            throw new RuntimeException('Request attributes are not valid.');
        }

        $context = $this->decorated->createFromRequest($request, $normalization, $attributes);

        if (!($operation = $context['operation'] ?? null)) {
            $operation = $this->resourceMetadataCollectionFactory->create($attributes['resource_class'])->getOperation($attributes['operation_name'] ?? null);
        }

        $resourceFilters = $operation->getFilters();

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
