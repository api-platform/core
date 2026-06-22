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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\UnionIriCollection\Bar;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\UnionIriCollection\Container;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\UnionIriCollection\Foo;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class UnionIriCollectionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Container::class, Foo::class, Bar::class];
    }

    public function testDenormalizeCollectionAcceptsIriOfEachUnionMember(): void
    {
        $response = self::createClient()->request('POST', '/union_iri_collection_containers', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'json' => ['attachments' => ['/union_iri_collection_foos/1', '/union_iri_collection_bars/2']],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'attachments' => [
                '/union_iri_collection_foos/1',
                '/union_iri_collection_bars/2',
            ],
        ]);
    }
}
