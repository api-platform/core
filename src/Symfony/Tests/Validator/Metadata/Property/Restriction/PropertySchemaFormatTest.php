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
use ApiPlatform\Symfony\Validator\Metadata\Property\Restriction\PropertySchemaFormat;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
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

    private PropertySchemaFormat $propertySchemaFormatRestriction;

    protected function setUp(): void
    {
        $this->propertySchemaFormatRestriction = new PropertySchemaFormat();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('supportsProvider')]
    public function testSupports(Constraint $constraint, ApiProperty $propertyMetadata, bool $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaFormatRestriction->supports($constraint, $propertyMetadata));
    }

    public static function supportsProvider(): \Generator
    {
        yield 'email' => [new Email(), new ApiProperty(), true];
        yield 'url' => [new Url(requireTld: true), new ApiProperty(), true];
        if (class_exists(Hostname::class)) {
            yield 'hostname' => [new Hostname(), new ApiProperty(), true];
        }
        yield 'uuid' => [new Uuid(), new ApiProperty(), true];
        if (class_exists(Ulid::class)) {
            yield 'ulid' => [new Ulid(), new ApiProperty(), true];
        }
        yield 'ip' => [new Ip(), new ApiProperty(), true];
        yield 'not supported' => [new Positive(), new ApiProperty(), false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('createProvider')]
    public function testCreate(Constraint $constraint, ApiProperty $propertyMetadata, array $expectedResult): void
    {
        self::assertSame($expectedResult, $this->propertySchemaFormatRestriction->create($constraint, $propertyMetadata));
    }

    public static function createProvider(): \Generator
    {
        yield 'email' => [new Email(), new ApiProperty(), ['format' => 'email']];
        yield 'url' => [new Url(requireTld: true), new ApiProperty(), ['format' => 'uri']];
        if (class_exists(Hostname::class)) {
            yield 'hostname' => [new Hostname(), new ApiProperty(), ['format' => 'hostname']];
        }
        yield 'uuid' => [new Uuid(), new ApiProperty(), ['format' => 'uuid']];
        if (class_exists(Ulid::class)) {
            yield 'ulid' => [new Ulid(), new ApiProperty(), ['format' => 'ulid']];
        }
        yield 'ipv4' => [new Ip(['version' => '4']), new ApiProperty(), ['format' => 'ipv4']];
        yield 'ipv6' => [new Ip(['version' => '6']), new ApiProperty(), ['format' => 'ipv6']];
        yield 'not supported' => [new Positive(), new ApiProperty(), []];
    }
}
