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

namespace ApiPlatform\Core\Tests\Bridge\RamseyUuid\Normalizer;

use ApiPlatform\Core\Bridge\RamseyUuid\Identifier\Normalizer\UuidNormalizer;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UuidNormalizerTest extends TestCase
{
    public function testDenormalizeUuid()
    {
        $uuid = Uuid::uuid4();
        $normalizer = new UuidNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization($uuid->toString(), Uuid::class));
        $this->assertEquals($uuid, $normalizer->denormalize($uuid->toString(), Uuid::class));
    }

    public function testNoSupportDenormalizeUuid()
    {
        $uuid = 'notanuuid';
        $normalizer = new UuidNormalizer();
        $this->assertFalse($normalizer->supportsDenormalization($uuid, ''));
    }

    public function testFailDenormalizeUuid()
    {
        $this->expectException(\ApiPlatform\Core\Exception\InvalidIdentifierException::class);

        $uuid = 'notanuuid';
        $normalizer = new UuidNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization($uuid, Uuid::class));
        $normalizer->denormalize($uuid, Uuid::class);
    }
}
