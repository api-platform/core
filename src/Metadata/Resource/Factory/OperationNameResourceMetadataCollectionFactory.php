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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Creates a resource metadata from {@see Resource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class OperationNameResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ?ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);

        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = $resource->getOperations();

            foreach ($operations as $operationName => $operation) {
                if ($operation->getName()) {
                    continue;
                }

                if ($operation->getRouteName()) {
                    $operations->add($operationName, $operation->withName($operation->getRouteName()));
                    continue;
                }

                $path = ($operation->getRoutePrefix() ?? '').($operation->getUriTemplate() ?? '');
                $newOperationName = sprintf('_api_%s_%s%s', $path ?: ($operation->getShortName() ?? $this->getDefaultShortname($resourceClass)), strtolower($operation->getMethod() ?? HttpOperation::METHOD_GET), $operation instanceof CollectionOperationInterface ? '_collection' : '');
                $operations->remove($operationName)->add($newOperationName, $operation->withName($newOperationName));
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations->sort());
        }

        return $resourceMetadataCollection;
    }

    private function getDefaultShortname(string $resourceClass): string
    {
        return (false !== $pos = strrpos($resourceClass, '\\')) ? substr($resourceClass, $pos + 1) : $resourceClass;
    }
}
