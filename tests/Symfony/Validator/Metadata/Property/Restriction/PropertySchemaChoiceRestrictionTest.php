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
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaChoiceRestriction;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaChoiceRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private PropertySchemaChoiceRestriction $propertySchemaChoiceRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaChoiceRestriction = new PropertySchemaChoiceRestriction();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaChoiceRestriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProvider(): \Generator
    {
        yield 'supported string' => [new Choice(['choices' => ['a', 'b']]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), true];
        yield 'supported int' => [new Choice(['choices' => [1, 2]]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), true];
        yield 'supported float' => [new Choice(['choices' => [1.1, 2.2]]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]), true];
        yield 'supported string/int/float with union types' => [new Choice(['choices' => [1, 2, 1.1, 2.2, 'a', 'b']]), (new ApiProperty())->withBuiltinTypes([
            new Type(Type::BUILTIN_TYPE_FLOAT),
            new Type(Type::BUILTIN_TYPE_INT),
            new Type(Type::BUILTIN_TYPE_STRING),
        ]), true];

        yield 'not supported constraint' => [new Positive(), new ApiProperty(), false];
        yield 'not supported type' => [new Choice(['choices' => [new \stdClass(), new \stdClass()]]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT)]), false];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(Constraint $constraint, ApiProperty $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaChoiceRestriction->create($constraint, $propertyMetadata));
    }

    public static function createProvider(): \Generator
    {
        yield 'single string choice' => [new Choice(['choices' => ['a', 'b']]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), ['enum' => ['a', 'b']]];
        yield 'multi string choice' => [new Choice(['choices' => ['a', 'b'], 'multiple' => true]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b']]]];
        yield 'multi string choice min' => [new Choice(['choices' => ['a', 'b'], 'multiple' => true, 'min' => 2]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b']], 'minItems' => 2]];
        yield 'multi string choice max' => [new Choice(['choices' => ['a', 'b', 'c', 'd'], 'multiple' => true, 'max' => 4]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'maxItems' => 4]];
        yield 'multi string choice min/max' => [new Choice(['choices' => ['a', 'b', 'c', 'd'], 'multiple' => true, 'min' => 2, 'max' => 4]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']], 'minItems' => 2, 'maxItems' => 4]];

        yield 'single int choice' => [new Choice(['choices' => [1, 2]]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), ['enum' => [1, 2]]];
        yield 'multi int choice' => [new Choice(['choices' => [1, 2], 'multiple' => true]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), ['type' => 'array', 'items' => ['type' => 'number', 'enum' => [1, 2]]]];
        yield 'multi int choice min' => [new Choice(['choices' => [1, 2], 'multiple' => true, 'min' => 2]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), ['type' => 'array', 'items' => ['type' => 'number', 'enum' => [1, 2]], 'minItems' => 2]];
        yield 'multi int choice max' => [new Choice(['choices' => [1, 2, 3, 4], 'multiple' => true, 'max' => 4]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), ['type' => 'array', 'items' => ['type' => 'number', 'enum' => [1, 2, 3, 4]], 'maxItems' => 4]];
        yield 'multi int choice min/max' => [new Choice(['choices' => [1, 2, 3, 4], 'multiple' => true, 'min' => 2, 'max' => 4]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), ['type' => 'array', 'items' => ['type' => 'number', 'enum' => [1, 2, 3, 4]], 'minItems' => 2, 'maxItems' => 4]];

        yield 'single float choice' => [new Choice(['choices' => [1.1, 2.2]]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]), ['enum' => [1.1, 2.2]]];
        yield 'multi float choice' => [new Choice(['choices' => [1.1, 2.2], 'multiple' => true]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]), ['type' => 'array', 'items' => ['type' => 'number', 'enum' => [1.1, 2.2]]]];
        yield 'multi float choice min' => [new Choice(['choices' => [1.1, 2.2], 'multiple' => true, 'min' => 2]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]), ['type' => 'array', 'items' => ['type' => 'number', 'enum' => [1.1, 2.2]], 'minItems' => 2]];
        yield 'multi float choice max' => [new Choice(['choices' => [1.1, 2.2, 3.3, 4.4], 'multiple' => true, 'max' => 4]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]), ['type' => 'array', 'items' => ['type' => 'number', 'enum' => [1.1, 2.2, 3.3, 4.4]], 'maxItems' => 4]];
        yield 'multi float choice min/max' => [new Choice(['choices' => [1.1, 2.2, 3.3, 4.4], 'multiple' => true, 'min' => 2, 'max' => 4]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]), ['type' => 'array', 'items' => ['type' => 'number', 'enum' => [1.1, 2.2, 3.3, 4.4]], 'minItems' => 2, 'maxItems' => 4]];

        yield 'single string/int/float choice with union types' => [new Choice(['choices' => [1, 2, 'a', 'b', 1.1, 2.2]]), (new ApiProperty())->withBuiltinTypes([
            new Type(Type::BUILTIN_TYPE_STRING),
            new Type(Type::BUILTIN_TYPE_INT),
            new Type(Type::BUILTIN_TYPE_FLOAT),
        ]), ['enum' => [1, 2, 'a', 'b', 1.1, 2.2]]];
        yield 'multi string/int/float choice with union types' => [new Choice(['choices' => [1, 2, 'a', 'b', 1.1, 2.2], 'multiple' => true]), (new ApiProperty())->withBuiltinTypes([
            new Type(Type::BUILTIN_TYPE_STRING),
            new Type(Type::BUILTIN_TYPE_INT),
            new Type(Type::BUILTIN_TYPE_FLOAT),
        ]), ['type' => 'array', 'items' => ['type' => ['string', 'number'], 'enum' => [1, 2, 'a', 'b', 1.1, 2.2]]]];
        yield 'multi string/int/float choice min with union types' => [new Choice(['choices' => [1, 2, 'a', 'b', 1.1, 2.2], 'multiple' => true, 'min' => 2]), (new ApiProperty())->withBuiltinTypes([
            new Type(Type::BUILTIN_TYPE_STRING),
            new Type(Type::BUILTIN_TYPE_INT),
            new Type(Type::BUILTIN_TYPE_FLOAT),
        ]), ['type' => 'array', 'items' => ['type' => ['string', 'number'], 'enum' => [1, 2, 'a', 'b', 1.1, 2.2]], 'minItems' => 2]];
        yield 'multi string/int/float choice max with union types' => [new Choice(['choices' => [1, 2, 'a', 'b', 1.1, 2.2, 3.3, 4.4], 'multiple' => true, 'max' => 4]), (new ApiProperty())->withBuiltinTypes([
            new Type(Type::BUILTIN_TYPE_STRING),
            new Type(Type::BUILTIN_TYPE_INT),
            new Type(Type::BUILTIN_TYPE_FLOAT),
        ]), ['type' => 'array', 'items' => ['type' => ['string', 'number'], 'enum' => [1, 2, 'a', 'b', 1.1, 2.2, 3.3, 4.4]], 'maxItems' => 4]];
        yield 'multi string/int/float choice min/max with union types' => [new Choice(['choices' => [1, 2, 'a', 'b', 1.1, 2.2, 3.3, 4.4], 'multiple' => true, 'min' => 2, 'max' => 4]), (new ApiProperty())->withBuiltinTypes([
            new Type(Type::BUILTIN_TYPE_STRING),
            new Type(Type::BUILTIN_TYPE_INT),
            new Type(Type::BUILTIN_TYPE_FLOAT),
        ]), ['type' => 'array', 'items' => ['type' => ['string', 'number'], 'enum' => [1, 2, 'a', 'b', 1.1, 2.2, 3.3, 4.4]], 'minItems' => 2, 'maxItems' => 4]];

        yield 'single choice callback' => [new Choice(['callback' => ChoiceCallback::getChoices(...)]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), ['enum' => ['a', 'b', 'c', 'd']]];
        yield 'multi choice callback' => [new Choice(['callback' => ChoiceCallback::getChoices(...), 'multiple' => true]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)]), ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c', 'd']]]];
    }
}

final class ChoiceCallback
{
    public static function getChoices(): array
    {
        return ['a', 'b', 'c', 'd'];
    }
}
