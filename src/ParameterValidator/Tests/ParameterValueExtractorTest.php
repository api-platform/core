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

namespace ApiPlatform\ParameterValidator\Tests;

use ApiPlatform\ParameterValidator\ParameterValueExtractor;
use PHPUnit\Framework\TestCase;

/**
 * @author Nicolas LAURENT <nicolas.laurent@les-tilleuls.coop>
 */
class ParameterValueExtractorTest extends TestCase
{
    private const SUPPORTED_SEPARATORS = [
        'csv' => ',',
        'ssv' => ' ',
        'tsv' => '\t',
        'pipes' => '|',
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
    }

    /**
     * @dataProvider provideGetCollectionFormatCases
     */
    public function testGetCollectionFormat(array $filterDescription, string $expectedResult): void
    {
        $this->assertSame($expectedResult, ParameterValueExtractor::getCollectionFormat($filterDescription));
    }

    /**
     * @return iterable<array{array, string}>
     */
    public function provideGetCollectionFormatCases(): iterable
    {
        yield 'empty description' => [
            [], 'csv',
        ];

        yield 'swagger description' => [
            ['swagger' => ['collectionFormat' => 'foo']], 'foo',
        ];

        yield 'openapi description' => [
            ['openapi' => ['collectionFormat' => 'bar']], 'bar',
        ];
    }

    /**
     * @dataProvider provideGetSeparatorCases
     */
    public function testGetSeparator(string $separatorName, string $expectedSeparator, string|null $expectedException): void
    {
        if ($expectedException) {
            $this->expectException($expectedException);
        }
        self::assertSame($expectedSeparator, ParameterValueExtractor::getSeparator($separatorName));
    }

    /**
     * @return iterable<array{string, string, string|null}>
     */
    public function provideGetSeparatorCases(): iterable
    {
        yield 'empty separator' => [
            '', '', \InvalidArgumentException::class,
        ];

        foreach (self::SUPPORTED_SEPARATORS as $separatorName => $expectedSeparator) {
            yield "using '{$separatorName}'" => [
                $separatorName, $expectedSeparator, null,
            ];
        }
    }

    /**
     * @dataProvider provideGetValueCases
     *
     * @param int[]|string[]            $expectedValue
     * @param int|int[]|string|string[] $value
     */
    public function testGetValue(array $expectedValue, int|string|array $value, string $collectionFormat): void
    {
        self::assertSame($expectedValue, ParameterValueExtractor::getValue($value, $collectionFormat));
    }

    /**
     * @return iterable<array{int[]|string[], int|string|int[]|string[], string}>
     */
    public function provideGetValueCases(): iterable
    {
        yield 'empty input' => [
            [], [], 'csv',
        ];

        yield 'comma separated value' => [
            ['foo', 'bar'], 'foo,bar', 'csv',
        ];

        yield 'space separated value' => [
            ['foo', 'bar'], 'foo bar', 'ssv',
        ];

        yield 'tab separated value' => [
            ['foo', 'bar'], 'foo\tbar', 'tsv',
        ];

        yield 'pipe separated value' => [
            ['foo', 'bar'], 'foo|bar', 'pipes',
        ];

        yield 'array values' => [
            ['foo', 'bar'], ['foo', 'bar'], 'csv',
        ];
    }
}
