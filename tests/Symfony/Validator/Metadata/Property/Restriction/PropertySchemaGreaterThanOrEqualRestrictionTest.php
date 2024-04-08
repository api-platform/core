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
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanOrEqualRestriction;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaGreaterThanOrEqualRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private PropertySchemaGreaterThanOrEqualRestriction $propertySchemaGreaterThanOrEqualRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaGreaterThanOrEqualRestriction = new PropertySchemaGreaterThanOrEqualRestriction();
    }

    /**
     * @group legacy
     *
     * @dataProvider legacySupportsProvider
     */
    public function testSupportsLegacy(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaGreaterThanOrEqualRestriction->supports($constraint, $propertyMetadata));
    }

    /**
     * @group legacy
     */
    public static function legacySupportsProvider(): \Generator
    {
        yield 'supported int/float with union types' => [new GreaterThanOrEqual(['value' => 10]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT), new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT)]), true];
        yield 'supported int' => [new GreaterThanOrEqual(['value' => 10]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]), true];
        yield 'supported float' => [new GreaterThanOrEqual(['value' => 10.99]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_FLOAT)]), true];
        yield 'supported positive or zero' => [new PositiveOrZero(), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]), true];
        yield 'not supported positive' => [new Positive(), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]), false];
        yield 'not supported property path' => [new GreaterThanOrEqual(['propertyPath' => 'greaterThanMe']), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)]), false];
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaGreaterThanOrEqualRestriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProvider(): \Generator
    {
        yield 'supported int/float with union types' => [new GreaterThanOrEqual(['value' => 10]), (new ApiProperty())->withBuiltinTypes(Type::union(Type::int(), Type::float())), true];
        yield 'supported int' => [new GreaterThanOrEqual(['value' => 10]), (new ApiProperty())->withBuiltinTypes(Type::int()), true];
        yield 'supported float' => [new GreaterThanOrEqual(['value' => 10.99]), (new ApiProperty())->withBuiltinTypes(Type::float()), true];
        yield 'supported positive or zero' => [new PositiveOrZero(), (new ApiProperty())->withBuiltinTypes(Type::int()), true];
        yield 'not supported positive' => [new Positive(), (new ApiProperty())->withBuiltinTypes(Type::int()), false];
        yield 'not supported property path' => [new GreaterThanOrEqual(['propertyPath' => 'greaterThanMe']), (new ApiProperty())->withBuiltinTypes(Type::int()), false];
    }

    /**
     * @group legacy
     */
    public function testCreateLegacy(): void
    {
        self::assertEquals(['minimum' => 10], $this->propertySchemaGreaterThanOrEqualRestriction->create(new GreaterThanOrEqual(['value' => 10]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_INT)])));
    }

    public function testCreate(): void
    {
        self::assertEquals(['minimum' => 10], $this->propertySchemaGreaterThanOrEqualRestriction->create(new GreaterThanOrEqual(['value' => 10]), (new ApiProperty())->withBuiltinTypes(Type::int())));
    }
}
