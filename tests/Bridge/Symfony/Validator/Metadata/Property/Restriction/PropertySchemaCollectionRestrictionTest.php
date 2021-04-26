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

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaCollectionRestriction;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaFormat;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaLengthRestriction;
use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaRegexRestriction;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Required;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaCollectionRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private $propertySchemaCollectionRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaCollectionRestriction = new PropertySchemaCollectionRestriction([
            new PropertySchemaLengthRestriction(),
            new PropertySchemaRegexRestriction(),
            new PropertySchemaFormat(),
            new PropertySchemaCollectionRestriction(),
        ]);
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, PropertyMetadata $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaCollectionRestriction->supports($constraint, $propertyMetadata));
    }

    public function supportsProvider(): \Generator
    {
        yield 'supported' => [new Collection(['fields' => []]), new PropertyMetadata(), true];

        yield 'not supported' => [new Positive(), new PropertyMetadata(), false];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(Constraint $constraint, PropertyMetadata $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaCollectionRestriction->create($constraint, $propertyMetadata));
    }

    public function createProvider(): \Generator
    {
        yield 'empty' => [new Collection(['fields' => []]), new PropertyMetadata(), ['type' => 'object', 'properties' => [], 'additionalProperties' => false]];

        yield 'with fields' => [
            new Collection([
                'allowExtraFields' => true,
                'fields' => [
                    'name' => new Required([
                        new NotBlank(),
                    ]),
                    'email' => [
                        new NotNull(),
                        new Length(['min' => 2, 'max' => 255]),
                        new Email(['mode' => Email::VALIDATION_MODE_LOOSE]),
                    ],
                    'phone' => new Optional([
                        new \Symfony\Component\Validator\Constraints\Type(['type' => 'string']),
                        new Regex(['pattern' => '/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/']),
                    ]),
                    'age' => new Optional([
                        new \Symfony\Component\Validator\Constraints\Type(['type' => 'int']),
                    ]),
                    'social' => new Collection([
                        'fields' => [
                            'githubUsername' => new NotNull(),
                        ],
                    ]),
                ],
            ]),
            new PropertyMetadata(),
            [
                'type' => 'object',
                'properties' => [
                    'name' => [],
                    'email' => ['format' => 'email'],
                    'phone' => ['pattern' => '^([+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*)$'],
                    'age' => [],
                    'social' => ['type' => 'object', 'properties' => ['githubUsername' => []], 'additionalProperties' => false, 'required' => ['githubUsername']],
                ],
                'additionalProperties' => true,
                'required' => ['name', 'email', 'social'],
            ],
        ];
    }
}
