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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    shortName: 'JsonLdDummyIdCollectionDto',
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_dummy_id_collection_dtos',
            output: DummyIdCollectionDtoOutput::class,
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class DummyIdCollectionDto
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $foo = '';

    public int $bar = 0;

    public static function provideCollection(): array
    {
        $a = new DummyIdCollectionDtoOutput();
        $a->id = 1;
        $a->foo = 'foo';
        $a->bar = 1;

        $b = new DummyIdCollectionDtoOutput();
        $b->id = 2;
        $b->foo = 'foo';
        $b->bar = 2;

        return [$a, $b];
    }
}

final class DummyIdCollectionDtoOutput
{
    public ?int $id = null;

    public string $foo = '';

    public int $bar = 0;
}
