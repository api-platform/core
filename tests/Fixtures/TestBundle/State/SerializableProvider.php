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

use ApiPlatform\Core\DataProvider\SerializerAwareDataProviderTrait;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\SerializableResource;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class SerializableProvider implements ProviderInterface
{
    use SerializerAwareDataProviderTrait;

    /**
     * {@inheritDoc}
     */
    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = [])
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
     * {@inheritDoc}
     */
    public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        return SerializableResource::class === $resourceClass;
    }
}
