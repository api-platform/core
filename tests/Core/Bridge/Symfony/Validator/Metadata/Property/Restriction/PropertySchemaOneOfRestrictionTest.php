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

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLengthRestriction;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaOneOfRestriction;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class PropertySchemaOneOfRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private $propertySchemaOneOfRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaOneOfRestriction = new PropertySchemaOneOfRestriction([
            new PropertySchemaLengthRestriction(),
            new PropertySchemaRegexRestriction(),
        ]);
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, PropertyMetadata $propertyMetadata, bool $expectedResult): void
    {
        if (!class_exists(AtLeastOneOf::class)) {
            self::markTestSkipped();
        }

        self::assertSame($expectedResult, $this->propertySchemaOneOfRestriction->supports($constraint, $propertyMetadata));
    }

    public function supportsProvider(): \Generator
    {
        yield 'supported' => [new AtLeastOneOf(['constraints' => []]), new PropertyMetadata(), true];

        yield 'not supported' => [new Positive(), new PropertyMetadata(), false];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(Constraint $constraint, PropertyMetadata $propertyMetadata, array $expectedResult): void
    {
        if (!class_exists(AtLeastOneOf::class)) {
            self::markTestSkipped();
        }

        self::assertSame($expectedResult, $this->propertySchemaOneOfRestriction->create($constraint, $propertyMetadata));
    }

    public function createProvider(): \Generator
    {
        yield 'empty' => [new AtLeastOneOf(['constraints' => []]), new PropertyMetadata(), []];

        yield 'not supported constraints' => [new AtLeastOneOf(['constraints' => [new Positive(), new Length(['min' => 3])]]), new PropertyMetadata(), []];

        yield 'one supported constraint' => [new AtLeastOneOf(['constraints' => [new Positive(), new Length(['min' => 3])]]), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING)), [
            'oneOf' => [['minLength' => 3]],
        ]];
    }
}
