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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Validator\Metadata\Property\Restriction;

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanOrEqualRestriction;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
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

    private $propertySchemaGreaterThanOrEqualRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaGreaterThanOrEqualRestriction = new PropertySchemaGreaterThanOrEqualRestriction();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, PropertyMetadata $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaGreaterThanOrEqualRestriction->supports($constraint, $propertyMetadata));
    }

    public function supportsProvider(): \Generator
    {
        yield 'supported int' => [new GreaterThanOrEqual(['value' => 10]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)), true];
        yield 'supported float' => [new GreaterThanOrEqual(['value' => 10.99]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_FLOAT)), true];
        yield 'supported positive or zero' => [new PositiveOrZero(), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)), true];
        yield 'not supported positive' => [new Positive(), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)), false];
        yield 'not supported property path' => [new GreaterThanOrEqual(['propertyPath' => 'greaterThanMe']), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)), false];
    }

    public function testCreate(): void
    {
        self::assertSame(['minimum' => 10], $this->propertySchemaGreaterThanOrEqualRestriction->create(new GreaterThanOrEqual(['value' => 10]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT))));
    }
}
