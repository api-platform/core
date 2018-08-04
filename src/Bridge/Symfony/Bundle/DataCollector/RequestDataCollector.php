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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DataCollector;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * @author Julien DENIAU <julien.deniau@gmail.com>
 */
final class RequestDataCollector extends DataCollector
{
    private $metadataFactory;
    private $filterLocator;

    public function __construct(ResourceMetadataFactoryInterface $metadataFactory, ContainerInterface $filterLocator)
    {
        $this->metadataFactory = $metadataFactory;
        $this->filterLocator = $filterLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $counters = ['ignored_filters' => 0];
        $resourceClass = $request->attributes->get('_api_resource_class');
        $resourceMetadata = $resourceClass ? $this->metadataFactory->create($resourceClass) : null;

        $filters = [];
        foreach ($resourceMetadata ? $resourceMetadata->getAttribute('filters', []) : [] as $id) {
            if ($this->filterLocator->has($id)) {
                $filters[$id] = \get_class($this->filterLocator->get($id));
                continue;
            }

            $filters[$id] = null;
            ++$counters['ignored_filters'];
        }

        $this->data = [
            'resource_class' => $resourceClass,
            'resource_metadata' => $resourceMetadata ? $this->cloneVar($resourceMetadata) : null,
            'acceptable_content_types' => $request->getAcceptableContentTypes(),
            'request_attributes' => RequestAttributesExtractor::extractAttributes($request),
            'filters' => $filters,
            'counters' => $counters,
        ];
    }

    public function getAcceptableContentTypes(): array
    {
        return $this->data['acceptable_content_types'] ?? [];
    }

    public function getResourceClass()
    {
        return $this->data['resource_class'] ?? null;
    }

    public function getResourceMetadata()
    {
        return $this->data['resource_metadata'] ?? null;
    }

    public function getRequestAttributes(): array
    {
        return $this->data['request_attributes'] ?? [];
    }

    public function getFilters(): array
    {
        return $this->data['filters'] ?? [];
    }

    public function getCounters(): array
    {
        return $this->data['counters'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'api_platform.data_collector.request';
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }
}
