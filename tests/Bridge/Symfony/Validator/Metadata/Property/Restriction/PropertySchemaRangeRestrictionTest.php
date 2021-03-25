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

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRangeRestriction;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
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

    private $propertySchemaRangeRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaRangeRestriction = new PropertySchemaRangeRestriction();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, PropertyMetadata $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaRangeRestriction->supports($constraint, $propertyMetadata));
    }

    public function supportsProvider(): \Generator
    {
        yield 'supported int' => [new Range(['min' => 1, 'max' => 10]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)), true];
        yield 'supported float' => [new Range(['min' => 1, 'max' => 10]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_FLOAT)), true];

        yield 'not supported constraint' => [new Length(['min' => 1]), new PropertyMetadata(), false];
        yield 'not supported type' => [new Range(['min' => 1]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING)), false];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(Constraint $constraint, PropertyMetadata $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaRangeRestriction->create($constraint, $propertyMetadata));
    }

    public function createProvider(): \Generator
    {
        yield 'int min' => [new Range(['min' => 1]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)), ['minimum' => 1]];
        yield 'int max' => [new Range(['max' => 10]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)), ['maximum' => 10]];

        yield 'float min' => [new Range(['min' => 1.5]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_FLOAT)), ['minimum' => 1.5]];
        yield 'float max' => [new Range(['max' => 10.5]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_FLOAT)), ['maximum' => 10.5]];
    }
}
