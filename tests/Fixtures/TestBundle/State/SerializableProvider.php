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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerAwareProviderInterface;
use ApiPlatform\State\SerializerAwareProviderTrait;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class SerializableProvider implements ProviderInterface, SerializerAwareProviderInterface
{
    use SerializerAwareProviderTrait;

    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        return $this->getSerializer()->deserialize(<<<'JSON'
{
    "id": 1,
    "foo": "Lorem",
    "bar": "Ipsum"
}
JSON
            , $operation->getClass(), 'json');
    }
}
