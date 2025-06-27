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

namespace ApiPlatform\Tests\Symfony\Validator\Metadata\Property\Restriction;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCssColorRestriction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\CssColor;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

final class PropertySchemaCssColorRestrictionTest extends TestCase
{
    private PropertySchemaCssColorRestriction $restriction;

    protected function setUp(): void
    {
        $this->restriction = new PropertySchemaCssColorRestriction();
    }

    #[DataProvider('supportsProvider')]
    public function testSupports(mixed $constraint, ApiProperty $propertyMetadata, bool $expected): void
    {
        self::assertSame($expected, $this->restriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProvider(): \Generator
    {
        yield 'supported CssColor' => [new CssColor(), new ApiProperty(), true];
        yield 'unsupported other constraint' => [new PositiveOrZero(), new ApiProperty(), false];
    }

    #[DataProvider('createProvider')]
    public function testCreate(CssColor $constraint, string $expectedPattern): void
    {
        $property = new ApiProperty();
        $result = $this->restriction->create($constraint, $property);

        self::assertArrayHasKey('pattern', $result);
        self::assertSame($expectedPattern, $result['pattern']);
    }

    public static function createProvider(): \Generator
    {
        yield 'HEX_LONG only' => [
            new CssColor(formats: [CssColor::HEX_LONG]),
            '^(#[0-9a-f]{6})$',
        ];

        yield 'HEX_SHORT and KEYWORDS' => [
            new CssColor(formats: [CssColor::HEX_SHORT, CssColor::KEYWORDS]),
            '^(#[0-9a-f]{3}|(transparent|currentColor))$',
        ];

        yield 'RGB + NAMED COLORS' => [
            new CssColor(formats: [CssColor::RGB, CssColor::BASIC_NAMED_COLORS]),
            '^(rgb\\(\\s*(0|255|25[0-4]|2[0-4]\\d|1\\d\\d|0?\\d?\\d),\\s*(0|255|25[0-4]|2[0-4]\\d|1\\d\\d|0?\\d?\\d),\\s*(0|255|25[0-4]|2[0-4]\\d|1\\d\\d|0?\\d?\\d)\\s*\\)|(black|silver|gray|white|maroon|red|purple|fuchsia|green|lime|olive|yellow|navy|blue|teal|aqua))$',
        ];
    }
}
