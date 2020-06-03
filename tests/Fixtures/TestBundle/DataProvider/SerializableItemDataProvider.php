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

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\DataProvider\SerializerAwareDataProviderInterface;
use ApiPlatform\Core\DataProvider\SerializerAwareDataProviderTrait;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Model\SerializableResource;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class SerializableItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface, SerializerAwareDataProviderInterface
{
    use SerializerAwareDataProviderTrait;

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        return $this->getSerializer()->deserialize(<<<'JSON'
{
    "id": 1,
    "foo": "Lorem",
    "bar": "Ipsum"
}
JSON
            , $resourceClass, 'json');
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return SerializableResource::class === $resourceClass;
    }
}
