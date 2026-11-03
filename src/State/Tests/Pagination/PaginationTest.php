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

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\Pagination;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @author Ali Ben Macha <contact@benmacha.tn>
 */
class PaginationTest extends TestCase
{
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
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(\sprintf('Cursor %s is invalid', $cursor));

        (new Pagination())->getOffset(new GetCollection(paginationItemsPerPage: 10), ['graphql_operation_name' => 'query', 'filters' => [$parameter => $cursor]]);
    }
}
