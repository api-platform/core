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

namespace ApiPlatform\Tests\RamseyUuid\UriVariableTransformer;

use ApiPlatform\Exception\InvalidUriVariableException;
use ApiPlatform\RamseyUuid\UriVariableTransformer\UuidUriVariableTransformer;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UuidUriVariableTransformerTest extends TestCase
{
    public function testDenormalizeUuid()
    {
        $uuid = Uuid::uuid4();
        $normalizer = new UuidUriVariableTransformer();
        $this->assertTrue($normalizer->supportsTransformation($uuid->toString(), [Uuid::class]));
        $this->assertEquals($uuid, $normalizer->transform($uuid->toString(), [Uuid::class]));
    }

    public function testNoSupportDenormalizeUuid()
    {
        $uuid = 'notanuuid';
        $normalizer = new UuidUriVariableTransformer();
        $this->assertFalse($normalizer->supportsTransformation($uuid, ['']));
    }

    public function testFailDenormalizeUuid()
    {
        $this->expectException(InvalidUriVariableException::class);

        $uuid = 'notanuuid';
        $normalizer = new UuidUriVariableTransformer();
        $this->assertTrue($normalizer->supportsTransformation($uuid, [Uuid::class]));
        $normalizer->transform($uuid, [Uuid::class]);
    }

    public function testDoNotSupportNotString()
    {
        $uuid = Uuid::uuid4();
        $normalizer = new UuidUriVariableTransformer();
        $this->assertFalse($normalizer->supportsTransformation($uuid, [Uuid::class]));
    }
}
