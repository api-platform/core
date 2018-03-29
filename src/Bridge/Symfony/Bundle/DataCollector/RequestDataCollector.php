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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

final class RequestDataCollector extends DataCollector
{
    private $metadataFactory;

    public function __construct(ResourceMetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $resourceClass = $request->attributes->get('_api_resource_class');
        $resourceMetadata = $resourceClass ? $this->metadataFactory->create($resourceClass) : null;

        $this->data = [
            'resource_class' => $resourceClass,
            'resource_metadata' => $resourceMetadata,
            'method' => $request->getMethod(),
            'acceptable_content_types' => $request->getAcceptableContentTypes(),
        ];
    }

    public function getMethod(): string
    {
        return $this->data['method'] ?? '';
    }

    public function getAcceptableContentTypes(): array
    {
        return $this->data['acceptable_content_types'] ?? [];
    }

    public function getResourceClass()
    {
        return $this->data['resource_class'];
    }

    public function getResourceMetadata()
    {
        return $this->data['resource_metadata'];
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
