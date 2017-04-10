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
use ApiPlatform\Core\Swagger\Util\SwaggerDefinitions;
use ApiPlatform\Core\Swagger\Util\SwaggerOperationDataGuard;
use ApiPlatform\Core\Swagger\Util\SwaggerOperationId;

final class ItemGetOperationExtractor implements SwaggerOperationExtractorInterface
{
    private $resourceMetadataFactory;
    private $swaggerDefinitions;

    /**
     * RouteDocumentationExtractor constructor.
     *
     * @param ResourceMetadataFactoryInterface $resourceMetadataFactory
     * @param SwaggerDefinitions               $swaggerDefinitions
     */
    public function __construct(
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        SwaggerDefinitions $swaggerDefinitions
    ) {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->swaggerDefinitions = $swaggerDefinitions;
    }

    public function extract(array $operationData): \ArrayObject
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($operationData['resourceClass']);
        $responseDefinitionKey = $this->swaggerDefinitions->get($operationData, false);

        $documentation = new \ArrayObject();
        $documentation['produces'] = $operationData['mimeTypes'];
        $documentation['summary'] = sprintf('Retrieves a %s resource.', $resourceMetadata->getShortName());
        $documentation['parameters'] = [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'type' => 'integer',
        ]];
        $documentation['responses'] = [
            '200' => [
                'description' => sprintf('%s resource response', $resourceMetadata->getShortName()),
                'schema' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
            ],
            '404' => ['description' => 'Resource not found'],
        ];

        $documentation['tags'] = [$resourceMetadata->getShortName()];
        $documentation['operationId'] = SwaggerOperationId::create($operationData, $resourceMetadata);

        return new \ArrayObject([
            $operationData['path'] => [strtolower($operationData['method']) => $documentation],
        ]);
    }

    public function supportsExtraction(array $operationData): bool
    {
        return SwaggerOperationDataGuard::check($operationData) && 'GET' === $operationData['method'] && !$operationData['isCollection'];
    }
}
