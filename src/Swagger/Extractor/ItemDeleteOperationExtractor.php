<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Extractor;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Swagger\Util\SwaggerOperationDataGuard;
use ApiPlatform\Core\Swagger\Util\SwaggerOperationId;

final class ItemDeleteOperationExtractor implements SwaggerOperationExtractorInterface
{
    private $resourceMetadataFactory;

    /**
     * RouteDocumentationExtractor constructor.
     *
     * @param ResourceMetadataFactoryInterface $resourceMetadataFactory
     */
    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function extract(array $operationData): \ArrayObject
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($operationData['resourceClass']);

        $documentation = new \ArrayObject();
        $documentation['summary'] = sprintf('Removes the %s resource.', $resourceMetadata->getShortName());
        $documentation['responses'] = [
            '204' => ['description' => sprintf('%s resource deleted', $resourceMetadata->getShortName())],
            '404' => ['description' => 'Resource not found'],
        ];

        $documentation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'type' => 'integer',
            'required' => true,
        ]];
        $documentation['tags'] = [$resourceMetadata->getShortName()];
        $documentation['operationId'] = SwaggerOperationId::create($operationData, $resourceMetadata);

        return new \ArrayObject([
            $operationData['path'] => [strtolower($operationData['method']) => $documentation],
        ]);
    }

    public function supportsExtraction(array $operationData): bool
    {
        return SwaggerOperationDataGuard::check($operationData) && 'DELETE' === $operationData['method'] && !$operationData['isCollection'];
    }
}
