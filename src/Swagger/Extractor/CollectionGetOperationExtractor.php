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
use ApiPlatform\Core\Swagger\Util\SwaggerFilterDefinitions;
use ApiPlatform\Core\Swagger\Util\SwaggerOperationDataGuard;
use ApiPlatform\Core\Swagger\Util\SwaggerOperationId;

final class CollectionGetOperationExtractor implements SwaggerOperationExtractorInterface
{
    private $resourceMetadataFactory;
    private $swaggerDefinitions;
    private $swaggerFilterDefinitions;

    /**
     * RouteDocumentationExtractor constructor.
     *
     * @param ResourceMetadataFactoryInterface $resourceMetadataFactory
     * @param SwaggerDefinitions               $swaggerDefinitions
     * @param SwaggerFilterDefinitions         $swaggerFilterDefinitions
     */
    public function __construct(
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        SwaggerDefinitions $swaggerDefinitions,
        SwaggerFilterDefinitions $swaggerFilterDefinitions
    ) {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->swaggerDefinitions = $swaggerDefinitions;
        $this->swaggerFilterDefinitions = $swaggerFilterDefinitions;
    }

    public function extract(array $operationData): \ArrayObject
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($operationData['resourceClass']);
        $responseDefinitionKey = $this->swaggerDefinitions->get($operationData, false);
        $filtersParameters = $this->swaggerFilterDefinitions->get($operationData);

        $documentation = new \ArrayObject();
        $documentation['produces'] = $operationData['mimeTypes'];
        $documentation['summary'] = sprintf('Retrieves the collection of %s resources.', $resourceMetadata->getShortName());
        $documentation['responses'] = [
                '200' => [
                    'description' => sprintf('%s collection response', $resourceMetadata->getShortName()),
                    'schema' => [
                        'type' => 'array',
                        'items' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
                    ],
                ],
            ];

        if ($filtersParameters) {
            $documentation['parameters'] = $filtersParameters;
        }

        $documentation['responses'] = [
            '200' => [
                'description' => sprintf('%s collection response', $resourceMetadata->getShortName()),
                'schema' => [
                    'type' => 'array',
                    'items' => ['$ref' => sprintf('#/definitions/%s', $responseDefinitionKey)],
                ],
            ],
        ];
        $documentation['tags'] = [$resourceMetadata->getShortName()];
        $documentation['operationId'] = SwaggerOperationId::create($operationData, $resourceMetadata);

        return new \ArrayObject([
            $operationData['path'] => [strtolower($operationData['method']) => $documentation],
        ]);
    }

    public function supportsExtraction(array $operationData): bool
    {
        return SwaggerOperationDataGuard::check($operationData) && 'GET' === $operationData['method'] && $operationData['isCollection'];
    }
}
