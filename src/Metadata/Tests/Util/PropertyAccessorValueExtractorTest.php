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

namespace ApiPlatform\Metadata\Tests\Util;

use ApiPlatform\Metadata\Util\PropertyAccessorValueExtractor;
use PHPUnit\Framework\TestCase;

enum PropertyAccessorValueExtractorTestStatus
{
    case ACTIVE;
}

final class PropertyAccessorValueExtractorTest extends TestCase
{
    public function testGetValueReturnsScalarProperty(): void
    {
        $object = new class {
            public string $tenant = 'tenant-1';
        };

        $this->assertSame('tenant-1', PropertyAccessorValueExtractor::getValue($object, 'tenant'));
    }

    public function testGetValueReturnsNestedIdentifierValue(): void
    {
        $object = new class {
            public object $tenant;

            public function __construct()
            {
                $this->tenant = new class {
                    public function getId(): string
                    {
                        return 'tenant-1';
                    }
                };
            }
        };

        $this->assertSame('tenant-1', PropertyAccessorValueExtractor::getValue($object, 'tenant'));
    }

    public function testGetValueReturnsBooleanPropertyAsString(): void
    {
        $object = new class {
            public bool $tenant = true;
        };

        $this->assertSame('true', PropertyAccessorValueExtractor::getValue($object, 'tenant'));
    }

    public function testGetValueReturnsNullPropertyAsString(): void
    {
        $object = new class {
            public ?string $tenant = null;
        };

        $this->assertSame('null', PropertyAccessorValueExtractor::getValue($object, 'tenant'));
    }

    public function testGetValueReturnsUnitEnumName(): void
    {
        $object = new class {
            public PropertyAccessorValueExtractorTestStatus $tenant;

            public function __construct()
            {
                $this->tenant = PropertyAccessorValueExtractorTestStatus::ACTIVE;
            }
        };

        $this->assertSame('ACTIVE', PropertyAccessorValueExtractor::getValue($object, 'tenant'));
    }
}
