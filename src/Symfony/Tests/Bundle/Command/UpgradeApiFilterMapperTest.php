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

namespace ApiPlatform\Symfony\Tests\Bundle\Command;

use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UpgradeApiFilterMapperTest extends TestCase
{
    /**
     * @param array{filterClass: string, castToNativeType: bool, nativeType: ?string, caseSensitive?: bool} $expected
     */
    #[DataProvider('ormMappings')]
    public function testMapOrm(string $filter, ?string $strategy, ?string $propertyNativeType, bool $isRelation, array $expected): void
    {
        $mapper = new UpgradeApiFilterMapper();
        $result = $mapper->map($filter, $strategy, $propertyNativeType, $isRelation);

        $this->assertSame($expected['filterClass'], $result->filterClass);
        $this->assertSame($expected['castToNativeType'], $result->castToNativeType);
        $this->assertSame($expected['nativeType'], $result->nativeType);
        $this->assertSame($expected['caseSensitive'] ?? false, $result->caseSensitive);
    }

    public static function ormMappings(): iterable
    {
        $orm = 'ApiPlatform\Doctrine\Orm\Filter\\';

        yield 'Boolean -> Exact+bool+cast' => [
            $orm.'BooleanFilter', null, 'bool', false,
            ['filterClass' => $orm.'ExactFilter', 'castToNativeType' => true, 'nativeType' => 'bool'],
        ];

        yield 'Numeric -> Exact+int+cast' => [
            $orm.'NumericFilter', null, 'int', false,
            ['filterClass' => $orm.'ExactFilter', 'castToNativeType' => true, 'nativeType' => 'int'],
        ];

        yield 'Numeric float keeps native float' => [
            $orm.'NumericFilter', null, 'float', false,
            ['filterClass' => $orm.'ExactFilter', 'castToNativeType' => true, 'nativeType' => 'float'],
        ];

        yield 'Order -> Sort, no cast/native' => [
            $orm.'OrderFilter', null, null, false,
            ['filterClass' => $orm.'SortFilter', 'castToNativeType' => false, 'nativeType' => null],
        ];

        // Legacy default is case-sensitive; the new search filters default to case-insensitive,
        // so a non-"i" strategy must opt back in with caseSensitive: true.
        yield 'Search partial -> PartialSearchFilter case-sensitive' => [
            $orm.'SearchFilter', 'partial', 'string', false,
            ['filterClass' => $orm.'PartialSearchFilter', 'castToNativeType' => false, 'nativeType' => null, 'caseSensitive' => true],
        ];

        yield 'Search ipartial -> PartialSearchFilter case-insensitive' => [
            $orm.'SearchFilter', 'ipartial', 'string', false,
            ['filterClass' => $orm.'PartialSearchFilter', 'castToNativeType' => false, 'nativeType' => null, 'caseSensitive' => false],
        ];

        yield 'Search exact -> ExactFilter' => [
            $orm.'SearchFilter', 'exact', 'string', false,
            ['filterClass' => $orm.'ExactFilter', 'castToNativeType' => false, 'nativeType' => null],
        ];

        yield 'Search iexact -> ExactFilter (no case option)' => [
            $orm.'SearchFilter', 'iexact', 'string', false,
            ['filterClass' => $orm.'ExactFilter', 'castToNativeType' => false, 'nativeType' => null],
        ];

        yield 'Search start -> StartSearchFilter case-sensitive' => [
            $orm.'SearchFilter', 'start', 'string', false,
            ['filterClass' => $orm.'StartSearchFilter', 'castToNativeType' => false, 'nativeType' => null, 'caseSensitive' => true],
        ];

        yield 'Search istart -> StartSearchFilter case-insensitive' => [
            $orm.'SearchFilter', 'istart', 'string', false,
            ['filterClass' => $orm.'StartSearchFilter', 'castToNativeType' => false, 'nativeType' => null, 'caseSensitive' => false],
        ];

        yield 'Search end -> EndSearchFilter case-sensitive' => [
            $orm.'SearchFilter', 'end', 'string', false,
            ['filterClass' => $orm.'EndSearchFilter', 'castToNativeType' => false, 'nativeType' => null, 'caseSensitive' => true],
        ];

        yield 'Search word_start -> WordStartSearchFilter case-sensitive' => [
            $orm.'SearchFilter', 'word_start', 'string', false,
            ['filterClass' => $orm.'WordStartSearchFilter', 'castToNativeType' => false, 'nativeType' => null, 'caseSensitive' => true],
        ];

        yield 'Search iword_start -> WordStartSearchFilter case-insensitive' => [
            $orm.'SearchFilter', 'iword_start', 'string', false,
            ['filterClass' => $orm.'WordStartSearchFilter', 'castToNativeType' => false, 'nativeType' => null, 'caseSensitive' => false],
        ];

        yield 'Search on relation -> IriFilter' => [
            $orm.'SearchFilter', 'exact', null, true,
            ['filterClass' => $orm.'IriFilter', 'castToNativeType' => false, 'nativeType' => null],
        ];

        yield 'Date survives' => [
            $orm.'DateFilter', null, null, false,
            ['filterClass' => $orm.'DateFilter', 'castToNativeType' => false, 'nativeType' => null],
        ];

        yield 'Range survives' => [
            $orm.'RangeFilter', null, null, false,
            ['filterClass' => $orm.'RangeFilter', 'castToNativeType' => false, 'nativeType' => null],
        ];

        yield 'Exists survives' => [
            $orm.'ExistsFilter', null, null, false,
            ['filterClass' => $orm.'ExistsFilter', 'castToNativeType' => false, 'nativeType' => null],
        ];

        yield 'custom filter passthrough' => [
            'App\Filter\CustomFilter', null, null, false,
            ['filterClass' => 'App\Filter\CustomFilter', 'castToNativeType' => false, 'nativeType' => null],
        ];
    }

    public function testOdmSearchMapsToOdmCanonical(): void
    {
        $odm = 'ApiPlatform\Doctrine\Odm\Filter\\';
        $mapper = new UpgradeApiFilterMapper();

        $result = $mapper->map($odm.'SearchFilter', 'partial', 'string', false);

        $this->assertSame($odm.'PartialSearchFilter', $result->filterClass);
    }
}
