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

namespace ApiPlatform\Tests\Symfony\UriVariableTransformer;

use ApiPlatform\Exception\InvalidUriVariableException;
use ApiPlatform\Symfony\UriVariableTransformer\UuidUriVariableTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

class UuidUriVariableTransformerTest extends TestCase
{
    private const UUID_STRINGS = [
        'c91d7356-279e-11ec-84bd-17d18bfd4001', // v1
        'b80f6db6-9782-315e-acb5-0a898d5e5f92', // v3
        '7f293b0e-47f9-4f54-9ea1-ec0e5f3075bd', // v4
        '280135ca-300d-5bd2-81b3-bb23a29684db', // v5
        '1ec279fa-18d1-68d6-8f3d-ab1820cba30f', // v6
    ];

    /** @var UuidUriVariableTransformer */
    private $transformer;

    public function testTransform(): void
    {
        foreach (self::UUID_STRINGS as $uuidString) {
            $uuid = $this->transformer->transform($uuidString, []);
            $this->assertInstanceOf(Uuid::class, $uuid);
            $this->assertEquals($uuidString, (string) $uuid);
        }
    }

    public function testTransformThrows(): void
    {
        $this->expectException(InvalidUriVariableException::class);
        $this->expectExceptionMessage('Invalid UUID: "Api-Platform-Core2.7');
        $this->transformer->transform('Api-Platform-Core2.7', []);
    }

    public function testSupportsTransformation(): void
    {
        foreach (self::UUID_STRINGS as $uuidString) {
            $this->assertTrue($this->transformer->supportsTransformation($uuidString, [Uuid::class]));
            $this->assertTrue($this->transformer->supportsTransformation($uuidString, [Uuid::v1()]));
            $this->assertFalse($this->transformer->supportsTransformation($uuidString, [Ulid::class]));
            $this->assertFalse($this->transformer->supportsTransformation($uuidString, [new Ulid()]));
        }
    }

    protected function setUp(): void
    {
        $this->transformer = new UuidUriVariableTransformer();
    }
}
