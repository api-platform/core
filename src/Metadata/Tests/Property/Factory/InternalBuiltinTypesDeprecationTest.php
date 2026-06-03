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

namespace ApiPlatform\Metadata\Tests\Property\Factory;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\AttributePropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\ExtractorPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyMetadataFactory;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyWithApiPropertyAttributes;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type as LegacyType;

/**
 * Regression test for #8173: internal factories must not emit
 * "ApiProperty::withBuiltinTypes()" / "getBuiltinTypes()" deprecations
 * when building property metadata.
 *
 * The public deprecated methods must keep emitting the deprecation for
 * userland callers, but ApiPlatform's own factories must use an internal
 * code path that does not trigger it.
 */
final class InternalBuiltinTypesDeprecationTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var list<string>
     */
    private array $deprecations = [];

    protected function setUp(): void
    {
        $this->deprecations = [];
        set_error_handler(function (int $errno, string $errstr): bool {
            $this->deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    public function testApiPropertyExposesInternalBuiltinTypesAccessors(): void
    {
        $this->assertTrue(method_exists(ApiProperty::class, 'internalGetBuiltinTypes'), 'ApiProperty must expose an internal getter to read builtin types without triggering the deprecation.');
        $this->assertTrue(method_exists(ApiProperty::class, 'internalWithBuiltinTypes'), 'ApiProperty must expose an internal setter to store builtin types without triggering the deprecation.');
    }

    public function testInternalBuiltinTypesAccessorsDoNotEmitDeprecation(): void
    {
        if (!method_exists(ApiProperty::class, 'internalWithBuiltinTypes') || !method_exists(ApiProperty::class, 'internalGetBuiltinTypes')) {
            $this->markTestSkipped('Internal builtin types accessors are not available; covered by the structural test.');
        }

        $types = class_exists(LegacyType::class) ? [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)] : [];

        $property = (new ApiProperty())->internalWithBuiltinTypes($types);
        $property->internalGetBuiltinTypes();
        $this->assertEmpty($this->deprecations, 'The internal builtin types accessors must not emit any deprecation. Got: '.implode("\n", $this->deprecations));
    }

    public function testPublicWithBuiltinTypesStillEmitsDeprecation(): void
    {
        $types = class_exists(LegacyType::class) ? [new LegacyType(LegacyType::BUILTIN_TYPE_STRING)] : [];

        (new ApiProperty())->withBuiltinTypes($types);

        $found = false;
        foreach ($this->deprecations as $message) {
            if (str_contains($message, 'ApiPlatform\Metadata\ApiProperty::withBuiltinTypes()')) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'The public withBuiltinTypes() method must still emit a deprecation for external callers.');
    }

    public function testPublicGetBuiltinTypesStillEmitsDeprecation(): void
    {
        $property = new ApiProperty();
        $property->getBuiltinTypes();

        $found = false;
        foreach ($this->deprecations as $message) {
            if (str_contains($message, 'ApiPlatform\Metadata\ApiProperty::getBuiltinTypes()')) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'The public getBuiltinTypes() method must still emit a deprecation for external callers.');
    }

    public function testAttributePropertyMetadataFactorySourceUsesInternalAccessors(): void
    {
        $source = file_get_contents((new \ReflectionClass(AttributePropertyMetadataFactory::class))->getFileName());

        $this->assertStringNotContainsString('->withBuiltinTypes(', $source, 'AttributePropertyMetadataFactory must not call the deprecated public withBuiltinTypes() on ApiProperty.');
        $this->assertStringNotContainsString('->getBuiltinTypes(', $source, 'AttributePropertyMetadataFactory must not call the deprecated public getBuiltinTypes() on ApiProperty.');
    }

    public function testExtractorPropertyMetadataFactorySourceUsesInternalAccessors(): void
    {
        $source = file_get_contents((new \ReflectionClass(ExtractorPropertyMetadataFactory::class))->getFileName());

        $this->assertStringNotContainsString('->withBuiltinTypes(', $source, 'ExtractorPropertyMetadataFactory must not call the deprecated public withBuiltinTypes() on ApiProperty.');
        $this->assertStringNotContainsString('->getBuiltinTypes(', $source, 'ExtractorPropertyMetadataFactory must not call the deprecated public getBuiltinTypes() on ApiProperty.');
    }

    public function testPropertyInfoPropertyMetadataFactorySourceUsesInternalAccessors(): void
    {
        $source = file_get_contents((new \ReflectionClass(PropertyInfoPropertyMetadataFactory::class))->getFileName());

        $this->assertStringNotContainsString('->withBuiltinTypes(', $source, 'PropertyInfoPropertyMetadataFactory must not call the deprecated public withBuiltinTypes() on ApiProperty.');
        $this->assertStringNotContainsString('->getBuiltinTypes(', $source, 'PropertyInfoPropertyMetadataFactory must not call the deprecated public getBuiltinTypes() on ApiProperty.');
    }
}
