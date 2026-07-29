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

namespace ApiPlatform\Elasticsearch\Tests\Esql;

use ApiPlatform\Elasticsearch\Esql\Paginator;
use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PaginatorTest extends TestCase
{
    use ProphecyTrait;

    private const RESPONSE = [
        'columns' => [
            ['name' => '_id', 'type' => 'keyword'],
            ['name' => 'name', 'type' => 'keyword'],
            ['name' => 'bar.baz', 'type' => 'keyword'],
        ],
        'values' => [
            ['1', 'Kilian', 'a'],
            ['2', 'Xavier', 'b'],
            ['3', 'René', 'c'],
        ],
    ];

    public function testPartialPaginationWithNextPage(): void
    {
        // limit 2, 3 rows fetched (limit + 1): a next page exists, the extra row is discarded
        $paginator = new Paginator($this->denormalizer(), self::RESPONSE, Foo::class, 2);

        self::assertCount(2, $paginator);
        self::assertTrue($paginator->hasNextPage());
        self::assertSame(1., $paginator->getCurrentPage());
        self::assertSame(2., $paginator->getItemsPerPage());
        self::assertCount(2, iterator_to_array($paginator));
    }

    public function testPartialPaginationWithoutNextPage(): void
    {
        $paginator = new Paginator($this->denormalizer(), self::RESPONSE, Foo::class, 3);

        self::assertCount(3, $paginator);
        self::assertFalse($paginator->hasNextPage());
        self::assertCount(3, iterator_to_array($paginator));
    }

    public function testRowsAreConvertedToDocuments(): void
    {
        $denormalizer = $this->prophesize(DenormalizerInterface::class);
        $denormalizer->denormalize(
            ['_source' => ['name' => 'Kilian', 'bar' => ['baz' => 'a']], '_id' => '1'],
            Foo::class,
            DocumentNormalizer::FORMAT,
            [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]
        )->willReturn(new Foo())->shouldBeCalled();

        $paginator = new Paginator($denormalizer->reveal(), [
            'columns' => self::RESPONSE['columns'],
            'values' => [self::RESPONSE['values'][0]],
        ], Foo::class, 10);

        iterator_to_array($paginator);
    }

    public function testEmptyResponse(): void
    {
        $paginator = new Paginator($this->prophesize(DenormalizerInterface::class)->reveal(), [], Foo::class, 10);

        self::assertCount(0, $paginator);
        self::assertFalse($paginator->hasNextPage());
        self::assertSame([], iterator_to_array($paginator));
    }

    private function denormalizer(): DenormalizerInterface
    {
        $denormalizer = $this->prophesize(DenormalizerInterface::class);
        $denormalizer->denormalize(Argument::cetera())->willReturn(new Foo());

        return $denormalizer->reveal();
    }
}
