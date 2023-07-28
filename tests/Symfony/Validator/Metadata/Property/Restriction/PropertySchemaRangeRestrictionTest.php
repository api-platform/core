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
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRangeRestriction;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type;
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

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaRangeRestriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProvider(): \Generator
    {
        yield 'supported int/float with union types' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_FLOAT)]), true];
        yield 'supported int' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), true];
        yield 'supported float' => [new Range(['min' => 1, 'max' => 10]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]), true];

        yield 'not supported constraint' => [new Length(['min' => 1]), new ApiProperty(), false];
        yield 'not supported type' => [new Range(['min' => 1]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), false];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(Constraint $constraint, ApiProperty $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaRangeRestriction->create($constraint, $propertyMetadata));
    }

    public static function createProvider(): \Generator
    {
        yield 'int min' => [new Range(['min' => 1]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), ['minimum' => 1]];
        yield 'int max' => [new Range(['max' => 10]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), ['maximum' => 10]];

        yield 'float min' => [new Range(['min' => 1.5]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]), ['minimum' => 1.5]];
        yield 'float max' => [new Range(['max' => 10.5]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]), ['maximum' => 10.5]];
    }
}
