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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\EntityClassAndCustomProviderResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SeparatedEntity;

class EntityClassAndCustomProviderResourceProvider implements ProviderInterface
{
    /**
     * Should probably be ProviderInterface for both with a binding in services.yaml in a real app.
     */
    public function __construct(
        private readonly ItemProvider $itemProvider,
        private readonly CollectionProvider $collectionProvider,
    ) {
    }

    /**
     * @return EntityClassAndCustomProviderResource[]|EntityClassAndCustomProviderResource|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $data = $this->collectionProvider->provide(
                $operation,
                $uriVariables,
                $context);

            $processed = [];

            foreach ($data as $item) {
                $processed[] = $this->transform($item);
            }

            return $processed;
        }

        $data = $this->itemProvider->provide(
            $operation,
            $uriVariables,
            $context
        );

        if (null === $data) {
            throw new ItemNotFoundException();
        }

        return $this->transform($data);
    }

    /**
     * Would do more in a real app...
     */
    private function transform(SeparatedEntity $data): EntityClassAndCustomProviderResource
    {
        $resource = new EntityClassAndCustomProviderResource();
        $resource->id = $data->id;
        $resource->value = $data->value;

        return $resource;
    }
}
