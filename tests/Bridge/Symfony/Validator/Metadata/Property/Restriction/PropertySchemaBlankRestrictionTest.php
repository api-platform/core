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

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaBlankRestriction;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @author Johnny van de Laar <j.vandelaar@pararius.nl>
 */
final class PropertySchemaBlankRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private $propertySchemaBlankRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaBlankRestriction = new PropertySchemaBlankRestriction();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, PropertyMetadata $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaBlankRestriction->supports($constraint, $propertyMetadata));
    }

    public function supportsProvider(): \Generator
    {
        yield 'blank string' => [new Blank(), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING)), true];
        yield 'not supported integer' => [new Blank(), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)), false];
        yield 'not supported constraint' => [new NotBlank(), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING)), false];
    }

    public function testCreate(): void
    {
        self::assertSame([
            'minLength' => 0,
            'maxLength' => 0,
        ], $this->propertySchemaBlankRestriction->create(new Blank(), new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING))));
    }
}
