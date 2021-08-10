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

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaUniqueRestriction;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Unique;

/**
 * @author Tomas Norkūnas <norkunas.tom@gmail.com>
 */
final class PropertySchemaUniqueRestrictionTest extends TestCase
{
    use ProphecyTrait;

    private $propertySchemaUniqueRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaUniqueRestriction = new PropertySchemaUniqueRestriction();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, PropertyMetadata $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaUniqueRestriction->supports($constraint, $propertyMetadata));
    }

    public function supportsProvider(): \Generator
    {
        yield 'supported' => [new Unique(), new PropertyMetadata(), true];

        yield 'not supported' => [new Positive(), new PropertyMetadata(), false];
    }

    public function testCreate(): void
    {
        self::assertSame(['uniqueItems' => true], $this->propertySchemaUniqueRestriction->create(new Unique(), new PropertyMetadata()));
    }
}
