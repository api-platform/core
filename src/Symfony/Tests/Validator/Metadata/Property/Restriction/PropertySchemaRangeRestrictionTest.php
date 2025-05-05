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
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRangeRestriction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaRangeRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private PropertySchemaRangeRestriction $propertySchemaRangeRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaRangeRestriction = new PropertySchemaRangeRestriction();
    }

    #[IgnoreDeprecations]
    public function testSupports(): void
    {
        foreach ($this->supportsProvider() as [$constraint, $propertyMetadata, $expectedResult]) {
            self::assertSame($expectedResult, $this->propertySchemaRangeRestriction->supports($constraint, $propertyMetadata));
        }
    }

    #[DataProvider('supportsProviderWithNativeType')]
    public function testSupportsWithNativeType(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaRangeRestriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProvider(): \Generator
    {
        yield 'supported int/float with union types' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT), new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT)]), true];
        yield 'supported int' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]), true];
        yield 'supported float' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT)]), true];

        yield 'not supported constraint' => [new Length(['min' => 1]), new ApiProperty(), false];
        yield 'not supported type' => [new Range(['min' => 1]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]), false];
    }

    public static function supportsProviderWithNativeType(): \Generator
    {
        yield 'native type: supported int/float with union types' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withNativeType(Type::union(Type::int(), Type::float())), true];
        yield 'native type: supported int' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withNativeType(Type::int()), true];
        yield 'native type: supported float' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withNativeType(Type::float()), true];

        yield 'native type: not supported constraint' => [new Length(['min' => 1]), (new ApiProperty())->withNativeType(Type::string()), false];
        yield 'native type: not supported type' => [new Range(['min' => 1]), (new ApiProperty())->withNativeType(Type::string()), false];
    }

    #[IgnoreDeprecations]
    public function testCreate(): void
    {
        foreach ($this->createProvider() as [$constraint, $propertyMetadata, $expectedResult]) {
            self::assertSame($expectedResult, $this->propertySchemaRangeRestriction->create($constraint, $propertyMetadata));
        }
    }

    #[DataProvider('createProviderWithNativeType')]
    public function testCreateWithNativeType(Range $constraint, ApiProperty $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaRangeRestriction->create($constraint, $propertyMetadata));
    }

    public static function createProvider(): \Generator
    {
        yield 'int min' => [new Range(['min' => 1]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]), ['minimum' => 1]];
        yield 'int max' => [new Range(['max' => 10]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]), ['maximum' => 10]];
        yield 'int min max' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]), ['minimum' => 1, 'maximum' => 10]];

        yield 'float min' => [new Range(['min' => 1.5]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT)]), ['minimum' => 1.5]];
        yield 'float max' => [new Range(['max' => 10.5]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT)]), ['maximum' => 10.5]];
        yield 'float min max' => [new Range(['min' => 1.5, 'max' => 10.5]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT)]), ['minimum' => 1.5, 'maximum' => 10.5]];
    }

    public static function createProviderWithNativeType(): \Generator
    {
        yield 'native type: int min' => [new Range(['min' => 1]), (new ApiProperty())->withNativeType(Type::int()), ['minimum' => 1]];
        yield 'native type: int max' => [new Range(['max' => 10]), (new ApiProperty())->withNativeType(Type::int()), ['maximum' => 10]];
        yield 'native type: int min max' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withNativeType(Type::int()), ['minimum' => 1, 'maximum' => 10]];

        yield 'native type: float min' => [new Range(['min' => 1.5]), (new ApiProperty())->withNativeType(Type::float()), ['minimum' => 1.5]];
        yield 'native type: float max' => [new Range(['max' => 10.5]), (new ApiProperty())->withNativeType(Type::float()), ['maximum' => 10.5]];
        yield 'native type: float min max' => [new Range(['min' => 1.5, 'max' => 10.5]), (new ApiProperty())->withNativeType(Type::float()), ['minimum' => 1.5, 'maximum' => 10.5]];
    }
}
