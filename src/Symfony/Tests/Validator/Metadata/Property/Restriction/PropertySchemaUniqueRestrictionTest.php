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
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaUniqueRestriction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Unique;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaUniqueRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private PropertySchemaUniqueRestriction $propertySchemaUniqueRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaUniqueRestriction = new PropertySchemaUniqueRestriction();
    }

    #[DataProvider('supportsProvider')]
    public function testSupports(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaUniqueRestriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProvider(): \Generator
    {
        yield 'supported' => [new Unique(), new ApiProperty(), true];

        yield 'not supported' => [new Positive(), new ApiProperty(), false];
    }

    public function testCreate(): void
    {
        self::assertEquals(['uniqueItems' => true], $this->propertySchemaUniqueRestriction->create(new Unique(), new ApiProperty()));
    }
}
