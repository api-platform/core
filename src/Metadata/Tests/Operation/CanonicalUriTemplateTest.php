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

namespace ApiPlatform\Metadata\Tests\Operation;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Query;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CanonicalUriTemplateTest extends TestCase
{
    public function testItCascadesFromTheResource(): void
    {
        $resource = new ApiResource(canonicalUriTemplate: '/books/{id}');

        $this->assertSame('/books/{id}', (new Patch())->cascadeFromResource($resource)->getCanonicalUriTemplate());
        $this->assertSame('/articles/{id}', (new Patch(canonicalUriTemplate: '/articles/{id}'))->cascadeFromResource($resource)->getCanonicalUriTemplate());
    }

    /**
     * @param class-string<GetCollection|Post|Query> $class
     */
    #[DataProvider('provideOperationsWithItems')]
    public function testItemsFallBackToTheCanonicalUriTemplate(string $class): void
    {
        $this->assertNull((new $class())->getItemUriTemplate());
        $this->assertSame('/books/{id}', (new $class(canonicalUriTemplate: '/books/{id}'))->getItemUriTemplate());
        $this->assertSame('/articles/{id}', (new $class(itemUriTemplate: '/articles/{id}', canonicalUriTemplate: '/books/{id}'))->getItemUriTemplate());
    }

    public static function provideOperationsWithItems(): iterable
    {
        yield [GetCollection::class];
        yield [Post::class];
        yield [Query::class];
    }
}
