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

namespace ApiPlatform\State\Tests\Pagination;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\Pagination;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testGraphQlCursorOffset(): void
    {
        $pagination = new Pagination();
        $operation = new GetCollection(paginationItemsPerPage: 10);

        $this->assertSame(10, $pagination->getOffset($operation, ['graphql_operation_name' => 'query', 'filters' => ['after' => base64_encode('9')]]));
        $this->assertSame(5, $pagination->getOffset($operation, ['graphql_operation_name' => 'query', 'filters' => ['before' => base64_encode('15')]]));
        $this->assertSame(0, $pagination->getOffset($operation, ['graphql_operation_name' => 'query', 'filters' => ['after' => '%invalid%']]));
    }

    public static function provideInvalidCursors(): iterable
    {
        foreach (['after', 'before'] as $parameter) {
            yield "negative $parameter" => [$parameter, base64_encode('-5')];
            yield "non-numeric $parameter" => [$parameter, base64_encode('abc')];
            yield "decimal $parameter" => [$parameter, base64_encode('1.5')];
            yield "out of range $parameter" => [$parameter, base64_encode('99999999999999999999')];
            yield "int max $parameter" => [$parameter, base64_encode((string) \PHP_INT_MAX)];
        }
    }

    #[DataProvider('provideInvalidCursors')]
    public function testGraphQlInvalidCursor(string $parameter, string $cursor): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor should be a base64 encoded non-negative integer');

        (new Pagination())->getOffset(new GetCollection(paginationItemsPerPage: 10), ['graphql_operation_name' => 'query', 'filters' => [$parameter => $cursor]]);
    }
}
