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

namespace ApiPlatform\GraphQl\Tests\Util;

use ApiPlatform\GraphQl\Util\PropertyAccessorValueExtractor;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
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

    public function testGetValueUsesConfiguredIdentifierInsteadOfGetId(): void
    {
        $tenant = new class {
            public function getId(): string
            {
                throw new \LogicException('The configured identifier is code.');
            }
        };
        $extractor = $this->createMock(IdentifiersExtractorInterface::class);
        $extractor->expects($this->once())->method('getIdentifiersFromItem')->with($tenant)->willReturn(['code' => 'tenant-1']);

        $this->assertSame('tenant-1', PropertyAccessorValueExtractor::getValue((object) ['tenant' => $tenant], 'tenant', $extractor));
    }

    public function testCompositeIdentifiersAreCanonicalAndKeepEveryComponent(): void
    {
        $tenant = new \stdClass();
        $extractor = $this->createStub(IdentifiersExtractorInterface::class);
        $extractor->method('getIdentifiersFromItem')->willReturnOnConsecutiveCalls(
            ['region' => 'eu', 'code' => '42'],
            ['code' => '42', 'region' => 'eu'],
            ['region' => 'us', 'code' => '42'],
        );
        $object = (object) ['tenant' => $tenant];
        $first = PropertyAccessorValueExtractor::getValue($object, 'tenant', $extractor);
        $this->assertSame('{"code":"42","region":"eu"}', $first);
        $this->assertSame($first, PropertyAccessorValueExtractor::getValue($object, 'tenant', $extractor));
        $this->assertNotSame($first, PropertyAccessorValueExtractor::getValue($object, 'tenant', $extractor));
    }

    public function testStringableResourceUsesIdentifiersInsteadOfDisplayName(): void
    {
        $tenant = new class implements \Stringable {
            public function __toString(): string
            {
                return 'Shared display name';
            }
        };
        $extractor = $this->createMock(IdentifiersExtractorInterface::class);
        $extractor->expects($this->once())->method('getIdentifiersFromItem')->with($tenant)->willReturn(['code' => 'tenant-1']);
        $resolver = $this->createStub(ResourceClassResolverInterface::class);
        $resolver->method('isResourceClass')->willReturn(true);

        $this->assertSame('tenant-1', PropertyAccessorValueExtractor::getValue((object) ['tenant' => $tenant], 'tenant', $extractor, $resolver));
    }

    public function testStringableValueObjectKeepsItsValue(): void
    {
        $value = new class implements \Stringable {
            public function __toString(): string
            {
                return 'tenant-uuid';
            }
        };
        $extractor = $this->createMock(IdentifiersExtractorInterface::class);
        $extractor->expects($this->never())->method('getIdentifiersFromItem');
        $resolver = $this->createStub(ResourceClassResolverInterface::class);
        $resolver->method('isResourceClass')->willReturn(false);

        $this->assertSame('tenant-uuid', PropertyAccessorValueExtractor::getValue((object) ['tenant' => $value], 'tenant', $extractor, $resolver));
    }

    public function testObjectWithoutIdentifiersIsRejected(): void
    {
        $extractor = $this->createStub(IdentifiersExtractorInterface::class);
        $extractor->method('getIdentifiersFromItem')->willReturn([]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No identifiers found for private field "tenant".');

        PropertyAccessorValueExtractor::getValue((object) ['tenant' => new \stdClass()], 'tenant', $extractor);
    }

    public function testObjectRequiresAnIdentifiersExtractor(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('An identifiers extractor is required');

        PropertyAccessorValueExtractor::getValue((object) ['tenant' => new \stdClass()], 'tenant');
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
