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
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanRestriction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaGreaterThanRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private PropertySchemaGreaterThanRestriction $propertySchemaGreaterThanRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaGreaterThanRestriction = new PropertySchemaGreaterThanRestriction();
    }

    #[DataProvider('supportsProviderWithNativeType')]
    public function testSupportsWithNativeType(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaGreaterThanRestriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProviderWithNativeType(): \Generator
    {
        yield 'native type: supported int/float with union types' => [new GreaterThan(value: 10), (new ApiProperty())->withNativeType(Type::union(Type::int(), Type::float())), true];
        yield 'native type: supported int' => [new GreaterThan(value: 10), (new ApiProperty())->withNativeType(Type::int()), true];
        yield 'native type: supported float' => [new GreaterThan(value: 10.99), (new ApiProperty())->withNativeType(Type::float()), true];
        yield 'native type: supported positive' => [new Positive(), (new ApiProperty())->withNativeType(Type::int()), true];
        yield 'native type: not supported positive or zero' => [new PositiveOrZero(), (new ApiProperty())->withNativeType(Type::int()), false];
        yield 'native type: not supported property path' => [new GreaterThan(propertyPath: 'greaterThanMe'), (new ApiProperty())->withNativeType(Type::int()), false];
    }

    public function testCreateWithNativeType(): void
    {
        self::assertEquals([
            'exclusiveMinimum' => 10,
            'minimum' => 10,
        ], $this->propertySchemaGreaterThanRestriction->create(new GreaterThan(value: 10), (new ApiProperty())->withNativeType(Type::int())));

        self::assertEquals([
            'exclusiveMinimum' => 0,
            'minimum' => 0,
        ], $this->propertySchemaGreaterThanRestriction->create(new Positive(), (new ApiProperty())->withNativeType(Type::int())));

        self::assertEquals([
            'exclusiveMinimum' => 10.99,
            'minimum' => 10.99,
        ], $this->propertySchemaGreaterThanRestriction->create(new GreaterThan(value: 10.99), (new ApiProperty())->withNativeType(Type::float())));
    }
}
