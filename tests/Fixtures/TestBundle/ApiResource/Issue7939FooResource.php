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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/issue7939_foos/{id}',
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class Issue7939FooResource
{
    public string $id = '';

    public static function provide(Operation $operation, array $uriVariables = [])
    {
        $r = new self();
        $r->id = (string) ($uriVariables['id'] ?? '');

        return $r;
    }
}
