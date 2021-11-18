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

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLessThanRestriction;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\Negative;
use Symfony\Component\Validator\Constraints\NegativeOrZero;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaLessThanRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private $propertySchemaLessThanRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaLessThanRestriction = new PropertySchemaLessThanRestriction();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaLessThanRestriction->supports($constraint, $propertyMetadata));
    }

    public function supportsProvider(): \Generator
    {
        yield 'supported int' => [new LessThan(['value' => 10]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), true];
        yield 'supported float' => [new LessThan(['value' => 10.99]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_FLOAT)]), true];
        yield 'supported negative' => [new Negative(), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), true];
        yield 'not supported negative or zero' => [new NegativeOrZero(), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), false];
        yield 'not supported property path' => [new LessThan(['propertyPath' => 'greaterThanMe']), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)]), false];
    }

    public function testCreate(): void
    {
        self::assertSame([
            'maximum' => 10,
            'exclusiveMaximum' => true,
        ], $this->propertySchemaLessThanRestriction->create(new LessThan(['value' => 10]), (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])));
    }
}
