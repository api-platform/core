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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\OtherResources\ResourceInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\OtherResources\ResourceInterfaceImplementation;

class ResourceInterfaceImplementationDataProvider implements ItemDataProviderInterface, CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return ResourceInterface::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        return (new ResourceInterfaceImplementation())->setFoo('single item');
    }

    public function getCollection(string $resourceClass, string $operationName = null)
    {
        yield (new ResourceInterfaceImplementation())->setFoo('item1');
        yield (new ResourceInterfaceImplementation())->setFoo('item2');
    }
}
