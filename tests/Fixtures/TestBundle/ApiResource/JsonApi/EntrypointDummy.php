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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'JsonApiEntrypointDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_entrypoint_dummies',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonapi_entrypoint_dummies/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class EntrypointDummy
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);

        return $r;
    }

    public static function provideCollection(): array
    {
        return [];
    }
}
