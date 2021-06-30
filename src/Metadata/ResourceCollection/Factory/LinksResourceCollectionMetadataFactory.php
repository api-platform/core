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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\AbstractDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ConcreteDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CustomActionDummy;
use ApiPlatform\Metadata\Operation;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class LinksResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
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
        $resourceMetadataCollection = new ResourceCollection();
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = iterator_to_array($resource->getOperations());

            foreach ($operations as $operationName => $operation) {
                $operations[$operationName] = $operation->withLinks($this->getLinks($resourceMetadataCollection, $operationName, $operation));
            }


            $resourceMetadataCollection[$i] = $resource->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }

    private function getLinks(ResourceCollection $resourceMetadata, string $resourceOperationName, Operation $currentOperation): array
    {
        $links = [];

        foreach ($resourceMetadata as $resource) {
            foreach ($resource->getOperations() as $operationName => $operation) {
                if ($operation->isCollection()) {
                    continue;
                }

                if ($operationName === $resourceOperationName) {
                    // TODO: 3.0 the current operation should take precedence using array_unshift, this breaks CustomActionDummy behavior which is wrong
                    if ($currentOperation->getExtraProperties()['is_legacy_resource_metadata'] ?? false) {
                        $links[] = [$operationName, $operation->getIdentifiers()];
                    } else {
                        array_unshift($links, [$operationName, $operation->getIdentifiers()]);
                    }
                    continue;
                }

                if (!$operation->getRouteName() && Operation::METHOD_GET === $operation->getMethod()) {
                    $links[] = [$operationName, $operation->getIdentifiers()];
                }
            }
        }

        return $links;
    }
}
