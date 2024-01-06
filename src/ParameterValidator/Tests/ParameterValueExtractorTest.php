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
}
