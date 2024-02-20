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
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCollectionRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaFormat;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaGreaterThanRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLengthRestriction;
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaCollectionRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private PropertySchemaCollectionRestriction $propertySchemaCollectionRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaCollectionRestriction = new PropertySchemaCollectionRestriction([
            new PropertySchemaGreaterThanRestriction(),
            new PropertySchemaLengthRestriction(),
            new PropertySchemaRegexRestriction(),
            new PropertySchemaFormat(),
            new PropertySchemaCollectionRestriction(),
        ]);
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaCollectionRestriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProvider(): \Generator
    {
        yield 'supported' => [new Collection(['fields' => []]), new ApiProperty(), true];

        yield 'not supported' => [new Positive(), new ApiProperty(), false];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(Constraint $constraint, ApiProperty $propertyMetadata, array $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->propertySchemaCollectionRestriction->create($constraint, $propertyMetadata));
    }

    public static function createProvider(): \Generator
    {
        yield 'empty' => [new Collection([]), new ApiProperty(), ['type' => 'object', 'properties' => [], 'additionalProperties' => false]];

        $fields = [
            'name' => new Required([
                new NotBlank(),
            ]),
            'email' => [
                new NotNull(),
                new Length(['min' => 2, 'max' => 255]),
                new Email(['mode' => Email::VALIDATION_MODE_HTML5]),
            ],
            'phone' => new Optional([
                new Type(['type' => 'string']),
                new Regex(['pattern' => '/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/']),
            ]),
            'age' => new Optional([
                new Type(['type' => 'int']),
                new GreaterThan(0),
            ]),
            'social' => new Collection([
                'fields' => [
                    'githubUsername' => new NotNull(),
                ],
            ]),
        ];
        $properties = [
            'name' => new \ArrayObject(),
            'email' => ['minLength' => 2, 'maxLength' => 255, 'format' => 'email'],
            'phone' => ['pattern' => '^([+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*)$'],
            'age' => ['exclusiveMinimum' => 0],
            'social' => ['type' => 'object', 'properties' => ['githubUsername' => new \ArrayObject()], 'additionalProperties' => false, 'required' => ['githubUsername']],
        ];
        $required = ['name', 'email', 'social'];

        yield 'with fields' => [
            new Collection([
                'fields' => $fields,
            ]),
            new ApiProperty(),
            [
                'type' => 'object',
                'properties' => $properties,
                'additionalProperties' => false,
                'required' => $required,
            ],
        ];

        yield 'with fields + allowExtraFields' => [
            new Collection([
                'fields' => $fields,
                'allowExtraFields' => true,
            ]),
            new ApiProperty(),
            [
                'type' => 'object',
                'properties' => $properties,
                'additionalProperties' => true,
                'required' => $required,
            ],
        ];

        yield 'with fields + allowMissingFields' => [
            new Collection([
                'fields' => $fields,
                'allowMissingFields' => true,
            ]),
            new ApiProperty(),
            [
                'type' => 'object',
                'properties' => $properties,
                'additionalProperties' => false,
            ],
        ];

        yield 'with fields + allowExtraFields + allowMissingFields' => [
            new Collection([
                'fields' => $fields,
                'allowExtraFields' => true,
                'allowMissingFields' => true,
            ]),
            new ApiProperty(),
            [
                'type' => 'object',
                'properties' => $properties,
                'additionalProperties' => true,
            ],
        ];
    }
}
