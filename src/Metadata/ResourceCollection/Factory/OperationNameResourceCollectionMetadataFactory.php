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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource;

/**
 * Creates a resource metadata from {@see Resource} annotations.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class OperationNameResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated = null)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceCollection
    {
        $parentResourceCollection = [];

        if ($this->decorated) {
            try {
                $parentResourceCollection = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            }
        }

        foreach ($parentResourceCollection as $i => $resource) {
            $operations = $resource->getOperations();

            foreach ($resource->getOperations() as $key => $operation) {
                $newOperationKey = sprintf('_api_%s_%s%s', $operation->getUriTemplate() ?: $operation->getShortName(), strtolower($operation->getMethod()), $operation instanceof GetCollection ? '_collection' : '');

                unset($operations[$key]);
                $operations[$newOperationKey] = $operation;
            }

            $parentResourceCollection[$i] = $resource->withOperations($operations);
        }

        return $parentResourceCollection;
    }
}
