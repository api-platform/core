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

namespace ApiPlatform\Symfony\Bundle\DataCollector;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Util\RequestAttributesExtractor;
use PackageVersions\Versions;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * @author Julien DENIAU <julien.deniau@gmail.com>
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class RequestDataCollector extends DataCollector
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $metadataFactory, private readonly ContainerInterface $filterLocator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        if ($request->attributes->get('_graphql', false)) {
            $resourceClasses = array_keys($request->attributes->get('_graphql_args', []));
        } else {
            $resourceClasses = array_filter([$request->attributes->get('_api_resource_class')]);
        }

        $requestAttributes = RequestAttributesExtractor::extractAttributes($request);
        if (isset($requestAttributes['previous_data'])) {
            $requestAttributes['previous_data'] = $this->cloneVar($requestAttributes['previous_data']);
        }

        $this->data['request_attributes'] = $requestAttributes;
        $this->data['acceptable_content_types'] = $request->getAcceptableContentTypes();
        $this->data['resources'] = array_map(fn (string $resourceClass): DataCollected => $this->collectDataByResource($resourceClass, $request), $resourceClasses);
    }

    private function setFilters(ApiResource $resourceMetadata, int $index, array &$filters, array &$counters): void
    {
        foreach ($resourceMetadata->getFilters() ?? [] as $id) {
            if ($this->filterLocator->has($id)) {
                $filters[$index][$id] = $this->filterLocator->get($id)::class;
                continue;
            }

            $filters[$index][$id] = null;
            ++$counters['ignored_filters'];
        }
    }

    public function getVersion(): ?string
    {
        if (!class_exists(Versions::class)) {
            return null;
        }

        $version = Versions::getVersion('api-platform/core');
        preg_match('/^v(.*?)@/', (string) $version, $output);

        return $output[1] ?? strtok($version, '@');
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'api_platform.data_collector.request';
    }

    public function getData(): array|Data
    {
        return $this->data;
    }

    public function getAcceptableContentTypes(): array
    {
        return $this->data['acceptable_content_types'] ?? [];
    }

    public function getRequestAttributes(): array
    {
        return $this->data['request_attributes'] ?? [];
    }

    public function getResources(): array
    {
        return $this->data['resources'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->data = [];
    }

    private function collectDataByResource(string $resourceClass, Request $request): DataCollected
    {
        $resourceMetadataCollection = $resourceClass ? $this->metadataFactory->create($resourceClass) : [];
        $filters = [];
        $counters = ['ignored_filters' => 0];
        $resourceMetadataCollectionData = [];

        /** @var ApiResource $resourceMetadata */
        foreach ($resourceMetadataCollection as $index => $resourceMetadata) {
            $this->setFilters($resourceMetadata, $index, $filters, $counters);
            $resourceMetadataCollectionData[] = [
                'resource' => $resourceMetadata,
                'operations' => null !== $resourceMetadata->getOperations() ? iterator_to_array($resourceMetadata->getOperations()) : [],
            ];
        }

        return new DataCollected(
            $resourceClass,
            $this->cloneVar($resourceMetadataCollectionData),
            $filters,
            $counters
        );
    }
}
