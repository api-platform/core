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

use ApiPlatform\Core\Bridge\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaFormat;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Ulid;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraints\Uuid;

final class PropertySchemaFormatTest extends TestCase
{
    use ProphecyTrait;

    private $propertySchemaFormatRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaFormatRestriction = new PropertySchemaFormat();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Constraint $constraint, PropertyMetadata $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaFormatRestriction->supports($constraint, $propertyMetadata));
    }

    public function supportsProvider(): \Generator
    {
        yield 'email' => [new Email(), new PropertyMetadata(), true];
        yield 'url' => [new Url(), new PropertyMetadata(), true];
        if (class_exists(Hostname::class)) {
            yield 'hostname' => [new Hostname(), new PropertyMetadata(), true];
        }
        yield 'uuid' => [new Uuid(), new PropertyMetadata(), true];
        if (class_exists(Ulid::class)) {
            yield 'ulid' => [new Ulid(), new PropertyMetadata(), true];
        }
        yield 'ip' => [new Ip(), new PropertyMetadata(), true];
        yield 'not supported' => [new Positive(), new PropertyMetadata(), false];
    }

    /**
     * @dataProvider createProvider
     */
    public function testCreate(Constraint $constraint, PropertyMetadata $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaFormatRestriction->create($constraint, $propertyMetadata));
    }

    public function createProvider(): \Generator
    {
        yield 'email' => [new Email(), new PropertyMetadata(), ['format' => 'email']];
        yield 'url' => [new Url(), new PropertyMetadata(), ['format' => 'uri']];
        if (class_exists(Hostname::class)) {
            yield 'hostname' => [new Hostname(), new PropertyMetadata(), ['format' => 'hostname']];
        }
        yield 'uuid' => [new Uuid(), new PropertyMetadata(), ['format' => 'uuid']];
        if (class_exists(Ulid::class)) {
            yield 'ulid' => [new Ulid(), new PropertyMetadata(), ['format' => 'ulid']];
        }
        yield 'ipv4' => [new Ip(['version' => '4']), new PropertyMetadata(), ['format' => 'ipv4']];
        yield 'ipv6' => [new Ip(['version' => '6']), new PropertyMetadata(), ['format' => 'ipv6']];
        yield 'not supported' => [new Positive(), new PropertyMetadata(), []];
    }
}
