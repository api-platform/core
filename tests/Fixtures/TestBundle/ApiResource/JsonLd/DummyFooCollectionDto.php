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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    shortName: 'JsonLdDummyFooCollectionDto',
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_dummy_foo_collection_dtos',
            itemUriTemplate: '/jsonld_dummy_foos/bar',
            provider: [self::class, 'provideCollection'],
        ),
        new Get(
            uriTemplate: '/jsonld_dummy_foos/bar',
            provider: [self::class, 'provide'],
        ),
    ],
)]
class DummyFooCollectionDto
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $foo = '';

    public int $bar = 0;

    public static function provideCollection(): array
    {
        $a = new self();
        $a->id = 1;
        $a->foo = 'foo';
        $a->bar = 1;

        $b = new self();
        $b->id = 2;
        $b->foo = 'foo';
        $b->bar = 2;

        return [$a, $b];
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = 1;
        $r->foo = 'foo';
        $r->bar = 1;

        return $r;
    }
}
