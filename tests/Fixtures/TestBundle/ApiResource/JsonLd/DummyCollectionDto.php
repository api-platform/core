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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    shortName: 'JsonLdDummyCollectionDto',
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_dummy_collection_dtos',
            output: DummyCollectionDtoOutput::class,
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class DummyCollectionDto
{
    public string $foo = '';

    public int $bar = 0;

    public static function provideCollection(): array
    {
        $a = new DummyCollectionDtoOutput();
        $a->foo = 'foo';
        $a->bar = 1;

        $b = new DummyCollectionDtoOutput();
        $b->foo = 'foo';
        $b->bar = 2;

        return [$a, $b];
    }
}

final class DummyCollectionDtoOutput
{
    public string $foo = '';

    public int $bar = 0;
}
