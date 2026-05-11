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
    shortName: 'JsonApiCustomOutput',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_custom_outputs',
            output: CustomOutputDto::class,
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonapi_custom_outputs/{id}',
            uriVariables: ['id'],
            output: CustomOutputDto::class,
            provider: [self::class, 'provide'],
        ),
    ],
)]
class CustomOutputResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $name = 'origin';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): CustomOutputDto
    {
        return new CustomOutputDto();
    }

    public static function provideCollection(): array
    {
        $a = new CustomOutputDto();
        $b = new CustomOutputDto();
        $b->bar = 2;

        return [$a, $b];
    }
}

final class CustomOutputDto
{
    public string $foo = 'test';

    public int $bar = 1;
}
