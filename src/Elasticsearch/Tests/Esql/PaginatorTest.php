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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PaginatorTest extends TestCase
{
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
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->once())
            ->method('denormalize')
            ->with(
                ['_source' => ['name' => 'Kilian', 'bar' => ['baz' => 'a']], '_id' => '1'],
                Foo::class,
                DocumentNormalizer::FORMAT,
                [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]
            )
            ->willReturn(new Foo());

        $paginator = new Paginator($denormalizer, [
            'columns' => self::RESPONSE['columns'],
            'values' => [self::RESPONSE['values'][0]],
        ], Foo::class, 10);

        iterator_to_array($paginator);
    }

    /**
     * A "text" field mapped with a sub-field is returned as two columns ("title" and
     * "title.keyword"): expanding the dotted name must not try to descend into the scalar.
     */
    public function testMultiFieldColumnsDoNotOverrideTheScalarColumn(): void
    {
        $documents = [];
        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturnCallback(
            static function (array $document) use (&$documents): Foo {
                $documents[] = $document;

                return new Foo();
            }
        );

        $paginator = new Paginator($denormalizer, [
            'columns' => [
                ['name' => '_id', 'type' => 'keyword'],
                ['name' => 'title', 'type' => 'text'],
                ['name' => 'title.keyword', 'type' => 'keyword'],
            ],
            'values' => [['1', 'Kilian', 'Kilian']],
        ], Foo::class, 10);

        self::assertCount(1, iterator_to_array($paginator));
        self::assertSame([['_source' => ['title' => 'Kilian'], '_id' => '1']], $documents);
    }

    public function testNestedColumnsAreExpanded(): void
    {
        $documents = [];
        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturnCallback(
            static function (array $document) use (&$documents): Foo {
                $documents[] = $document;

                return new Foo();
            }
        );

        $paginator = new Paginator($denormalizer, [
            'columns' => [
                ['name' => 'author.name', 'type' => 'keyword'],
                ['name' => 'author.address.city', 'type' => 'keyword'],
            ],
            'values' => [['Kilian', 'Lille']],
        ], Foo::class, 10);

        iterator_to_array($paginator);

        self::assertSame([['_source' => ['author' => ['name' => 'Kilian', 'address' => ['city' => 'Lille']]]]], $documents);
    }

    public function testDenormalizedDocumentsAreCached(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->exactly(3))
            ->method('denormalize')
            ->willReturnCallback(static fn (): Foo => new Foo());

        $paginator = new Paginator($denormalizer, self::RESPONSE, Foo::class, 10);

        $first = iterator_to_array($paginator);
        $second = iterator_to_array($paginator);

        self::assertCount(3, $first);
        self::assertSame($first, $second);
    }

    public function testDenormalizedDocumentsAreCachedWithoutIdColumn(): void
    {
        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->expects($this->exactly(2))
            ->method('denormalize')
            ->willReturnCallback(static fn (): Foo => new Foo());

        $paginator = new Paginator($denormalizer, [
            'columns' => [['name' => 'name', 'type' => 'keyword']],
            'values' => [['Kilian'], ['Xavier']],
        ], Foo::class, 10);

        $first = iterator_to_array($paginator);

        self::assertCount(2, $first);
        self::assertSame($first, iterator_to_array($paginator));
    }

    public function testEmptyResponse(): void
    {
        $paginator = new Paginator($this->createStub(DenormalizerInterface::class), [], Foo::class, 10);

        self::assertCount(0, $paginator);
        self::assertFalse($paginator->hasNextPage());
        self::assertSame([], iterator_to_array($paginator));
    }

    private function denormalizer(): DenormalizerInterface
    {
        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')->willReturn(new Foo());

        return $denormalizer;
    }
}
