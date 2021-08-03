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

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCountRestriction;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
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
    public function testSupports(Constraint $constraint, PropertyMetadata $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaCountRestriction->supports($constraint, $propertyMetadata));
    }

    public function supportsProvider(): \Generator
    {
        yield 'supported' => [new Count(['min' => 1]), new PropertyMetadata(), true];
        yield 'not supported' => [new Positive(), new PropertyMetadata(), false];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(Constraint $constraint, PropertyMetadata $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaCountRestriction->create($constraint, $propertyMetadata));
    }

    public function createProvider(): \Generator
    {
        yield 'min items' => [new Count(['min' => 1]), new PropertyMetadata(), ['minItems' => 1]];
        yield 'max items' => [new Count(['max' => 10]), new PropertyMetadata(), ['maxItems' => 10]];
        yield 'min/max items' => [new Count(['min' => 1, 'max' => 10]), new PropertyMetadata(), ['minItems' => 1, 'maxItems' => 10]];
    }
}
