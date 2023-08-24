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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State\Issue5605;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5605\MainResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5605\SubResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyWithSubEntity;

class MainResourceProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $itemProvider, private readonly ProviderInterface $collectionProvider)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof Get) {
            /**
             * @var DummyWithSubEntity $entity
             */
            $entity = $this->itemProvider->provide($operation, $uriVariables, $context);

            return $this->getResource($entity);
        }
        $resources = [];
        $entities = $this->collectionProvider->provide($operation, $uriVariables, $context);
        foreach ($entities as $entity) {
            $resources[] = $this->getResource($entity);
        }

        return $resources;
    }

    protected function getResource(DummyWithSubEntity $entity): MainResource
    {
        $resource = new MainResource();
        $resource->name = $entity->getName();
        $resource->id = $entity->getId();
        $resource->subResource = new SubResource();
        $resource->subResource->name = $entity->getSubEntity()->getName();
        $resource->subResource->strId = $entity->getSubEntity()->getStrId();

        return $resource;
    }
}
