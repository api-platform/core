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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5605\SubResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummySubEntity;

class SubResourceProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $itemProvider)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /**
         * @var DummySubEntity $entity
         */
        $entity = $this->itemProvider->provide($operation, $uriVariables, $context);
        $resource = new SubResource();
        $resource->strId = $entity->getStrId();
        $resource->name = $entity->getName();

        return $resource;
    }
}
