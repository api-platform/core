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

namespace ApiPlatform\Symfony\Tests\Validator\Metadata\Property\Restriction;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaRegexRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private PropertySchemaRegexRestriction $propertySchemaRegexRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaRegexRestriction = new PropertySchemaRegexRestriction();
    }

    #[DataProvider('supportsProvider')]
    public function testSupports(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaRegexRestriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProvider(): \Generator
    {
        yield 'supported' => [new Regex(pattern: '/^[0-9]+$/'), new ApiProperty(), true];
        yield 'supported too' => [new Regex(pattern: '/[0-9]/', match: false), new ApiProperty(), true];
        yield 'not supported' => [new Positive(), new ApiProperty(), false];
    }

    #[DataProvider('createProvider')]
    public function testCreate(Regex $constraint, ApiProperty $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaRegexRestriction->create($constraint, $propertyMetadata));
    }

    public static function createProvider(): \Generator
    {
        yield 'anchored' => [new Regex(pattern: '/^[0-9]+$/'), new ApiProperty(), ['pattern' => '^([0-9]+)$']];
        yield 'not anchored' => [new Regex(pattern: '/[0-9]/'), new ApiProperty(), ['pattern' => '^(.*[0-9].*)$']];
        yield 'inverted' => [new Regex(pattern: '/[0-9]/', match: false), new ApiProperty(), ['pattern' => '^(((?![0-9]).)*)$']];

        yield 'with options' => [new Regex(pattern: '/^[a-z]+$/i'), new ApiProperty(), []];
        yield 'with options and manual htmlPattern' => [new Regex(pattern: '/^[a-z]+$/i', htmlPattern: '[a-zA-Z]+'), new ApiProperty(), ['pattern' => '^([a-zA-Z]+)$']];
    }
}
