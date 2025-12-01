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
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLengthRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaOneOfRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class PropertySchemaOneOfRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private PropertySchemaOneOfRestriction $propertySchemaOneOfRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaOneOfRestriction = new PropertySchemaOneOfRestriction([
            new PropertySchemaLengthRestriction(),
            new PropertySchemaRegexRestriction(),
        ]);
    }

    #[IgnoreDeprecations]
    public function testSupports(): void
    {
        foreach ($this->supportsProvider() as [$constraint, $propertyMetadata, $expectedResult]) {
            self::assertSame($expectedResult, $this->propertySchemaOneOfRestriction->supports($constraint, $propertyMetadata));
        }
    }

    #[DataProvider('supportsProviderWithNativeType')]
    public function testSupportsWithNativeType(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaOneOfRestriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProvider(): \Generator
    {
        if (!class_exists(AtLeastOneOf::class)) {
            return;
        }

        yield 'supported' => [new AtLeastOneOf([new NotBlank()]), new ApiProperty(), true];
        yield 'not supported' => [new Positive(), new ApiProperty(), false];
    }

    public static function supportsProviderWithNativeType(): \Generator
    {
        if (!class_exists(AtLeastOneOf::class)) {
            return;
        }

        yield 'native type: supported' => [new AtLeastOneOf([new NotBlank()]), (new ApiProperty())->withNativeType(Type::mixed()), true];
        yield 'native type: not supported' => [new Positive(), (new ApiProperty())->withNativeType(Type::mixed()), false];
    }

    #[IgnoreDeprecations]
    public function testCreate(): void
    {
        if (!class_exists(LegacyType::class)) {
            $this->markTestSkipped('symfony/property-info is not installed.');
        }

        $cases = [
            'not supported constraints' => [new AtLeastOneOf([new Positive(), new Length(min: 3)]), new ApiProperty(), []],
            'one supported constraint' => [new AtLeastOneOf([new Positive(), new Length(min: 3)]), (new ApiProperty())->withBuiltinTypes([new LegacyType(LegacyType::BUILTIN_TYPE_STRING)]), [
                'oneOf' => [['minLength' => 3]],
            ]],
        ];

        foreach ($cases as [$constraint, $propertyMetadata, $expectedResult]) {
            self::assertSame($expectedResult, $this->propertySchemaOneOfRestriction->create($constraint, $propertyMetadata));
        }
    }

    #[DataProvider('createProviderWithNativeType')]
    public function testCreateWithNativeType(AtLeastOneOf $constraint, ApiProperty $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaOneOfRestriction->create($constraint, $propertyMetadata));
    }

    public static function createProviderWithNativeType(): \Generator
    {
        yield 'native type: not supported constraints' => [new AtLeastOneOf([new Positive(), new Length(min: 3)]), new ApiProperty(), []];
        yield 'native type: one supported constraint' => [new AtLeastOneOf([new Positive(), new Length(min: 3)]), (new ApiProperty())->withNativeType(Type::string()), [
            'oneOf' => [['minLength' => 3]],
        ]];
    }
}
