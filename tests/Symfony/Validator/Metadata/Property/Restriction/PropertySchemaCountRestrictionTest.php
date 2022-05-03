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

use ApiPlatform\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCountRestriction;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaCountRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private $propertySchemaCountRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaCountRestriction = new PropertySchemaCountRestriction();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaCountRestriction->supports($constraint, $propertyMetadata));
    }

    public function supportsProvider(): \Generator
    {
        yield 'supported' => [new Count(['min' => 1]), new ApiProperty(), true];
        yield 'not supported' => [new Positive(), new ApiProperty(), false];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(Constraint $constraint, ApiProperty $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaCountRestriction->create($constraint, $propertyMetadata));
    }

    public function createProvider(): \Generator
    {
        yield 'min items' => [new Count(['min' => 1]), new ApiProperty(), ['minItems' => 1]];
        yield 'max items' => [new Count(['max' => 10]), new ApiProperty(), ['maxItems' => 10]];
        yield 'min/max items' => [new Count(['min' => 1, 'max' => 10]), new ApiProperty(), ['minItems' => 1, 'maxItems' => 10]];
    }
}
