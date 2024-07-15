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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5921;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\Exception\TestException;

#[Get(
    uriTemplate: 'issue5921{._format}',
    read: true,
    provider: [ExceptionResource::class, 'provide'],
    extraProperties: ['_api_error_handler' => false]
)]
class ExceptionResource
{
    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): void
    {
        throw new TestException();
    }
}
