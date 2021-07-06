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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
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

        $hasSameOperationLink = false;
        foreach ($resourceMetadata as $resource) {
            foreach ($resource->getOperations() as $operationName => $operation) {
                if (!$operation->getRouteName() && Operation::METHOD_GET === $operation->getMethod()) {
                    if ($currentOperation->isCollection() === $operation->isCollection()) {
                        if (!$hasSameOperationLink) {
                            $hasSameOperationLink = true;
                            array_unshift($links, [$operationName, $operation->getIdentifiers(), $operation->getCompositeIdentifier(), $operation->isCollection()]);
                        }
                        continue;
                    }

                    array_push($links, [$operationName, $operation->getIdentifiers(), $operation->getCompositeIdentifier(), $operation->isCollection()]);
                }
            }
        }

        return $links;
    }
}
