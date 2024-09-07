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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State\Issue6590;

use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6590\OrmBarResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6590\Bar as BarEntity;

class BarResourceProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProviderInterface $itemProvider,
        private readonly ProviderInterface $collectionProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $entities = $this->collectionProvider->provide($operation, $uriVariables, $context);
            \assert($entities instanceof Paginator);

            $resources = [];
            foreach ($entities as $entity) {
                $resources[] = $this->getResource($entity);
            }

            return new TraversablePaginator(
                new \ArrayIterator($resources),
                $entities->getCurrentPage(),
                $entities->getItemsPerPage(),
                $entities->getTotalItems()
            );
        }

        $entity = $this->itemProvider->provide($operation, $uriVariables, $context);

        return $this->getResource($entity);
    }

    protected function getResource(BarEntity $entity): OrmBarResource
    {
        $resource = new OrmBarResource();
        $resource->id = $entity->getId();
        $resource->name = $entity->getName();

        return $resource;
    }
}
