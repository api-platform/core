<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Swagger\Util;

use ApiPlatform\Core\Api\OperationMethodResolverInterface;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;

class SwaggerOperationGenerator
{
    private $resourceMetadataFactory;
    private $operationPathResolver;
    private $operationMethodResolver;

    /**
     * DefaultExtractor constructor.
     *
     * @param ResourceMetadataFactoryInterface $resourceMetadataFactory
     * @param OperationPathResolverInterface   $operationPathResolver
     * @param OperationMethodResolverInterface $operationMethodResolver
     */
    public function __construct(
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        OperationPathResolverInterface $operationPathResolver,
        OperationMethodResolverInterface $operationMethodResolver
    ) {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->operationPathResolver = $operationPathResolver;
        $this->operationMethodResolver = $operationMethodResolver;
    }

    /**
     * @param Documentation $object
     *
     * @return array
     */
    public function generate(Documentation $object): array
    {
        $result = [];
        $resourceNameCollection = (array) $object->getResourceNameCollection()->getIterator();
        foreach ($resourceNameCollection as $resourceClass) {
            /** @var ResourceMetadata[] $metadata */
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $itemOperations = $resourceMetadata->getItemOperations();
            if (null !== $itemOperations) {
                foreach ($itemOperations as $operationName => $operation) {
                    $method = $this->operationMethodResolver->getItemOperationMethod($resourceClass, $operationName);
                    $path = $this->getPath($resourceMetadata->getShortName(), $operation, false);
                    $result[] = $this->prepareOperation($resourceClass, $operationName, $operation, false, $path, $method, $object->getMimeTypes());
                }
            }
            $collectionOperations = $resourceMetadata->getCollectionOperations();
            if (null !== $collectionOperations) {
                foreach ($collectionOperations as $operationName => $operation) {
                    $method = $this->operationMethodResolver->getCollectionOperationMethod($resourceClass, $operationName);
                    $path = $this->getPath($resourceMetadata->getShortName(), $operation, true);
                    $result[] = $this->prepareOperation($resourceClass, $operationName, $operation, true, $path, $method, $object->getMimeTypes());
                }
            }
        }

        return $result;
    }

    /**
     * @param string $resourceClass
     * @param string $operationName
     * @param array  $operation
     * @param bool   $isCollection
     * @param string $path
     * @param string $method
     * @param array  $mimeTypes
     *
     * @return array
     */
    private function prepareOperation(string $resourceClass, string $operationName, array $operation, bool $isCollection, string $path, string $method, array $mimeTypes)
    {
        return [
            'resourceClass' => $resourceClass,
            'operationName' => $operationName,
            'operation' => $operation,
            'isCollection' => $isCollection,
            'path' => $path,
            'method' => $method,
            'mimeTypes' => $mimeTypes,
        ];
    }

    /**
     * @param string $resourceShortName
     * @param array  $operation
     * @param bool   $collection
     *
     * @return string
     */
    private function getPath(string $resourceShortName, array $operation, bool $collection): string
    {
        $path = $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $collection);
        if ('.{_format}' === substr($path, -10)) {
            $path = substr($path, 0, -10);
        }

        return $path;
    }
}
