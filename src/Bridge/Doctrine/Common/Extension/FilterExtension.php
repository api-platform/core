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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Extension;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Api\FilterLocatorTrait;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\FilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Applies filters on a resource query.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class FilterExtension implements ContextAwareQueryCollectionExtensionInterface
{
    use FilterLocatorTrait;

    private $resourceMetadataFactory;

    /**
     * @param ContainerInterface|FilterCollection $filterLocator The new filter locator or the deprecated filter collection
     */
    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, $filterLocator)
    {
        $this->setFilterLocator($filterLocator);

        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection($builder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        if (empty($resourceFilters)) {
            return;
        }

        foreach ($resourceFilters as $filterId) {
            $filter = $this->getFilter($filterId);
            if ($filter instanceof FilterInterface) {
                $context['filters'] = $context['filters'] ?? [];
                $filter->apply($builder, $queryNameGenerator, $resourceClass, $operationName, $context);
            }
        }
    }
}
