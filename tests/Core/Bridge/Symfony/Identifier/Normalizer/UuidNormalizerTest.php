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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Identifier\Normalizer;

use ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UuidNormalizer;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;

final class UuidNormalizerTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(AbstractUid::class)) {
            $this->markTestSkipped();
        }
    }

    public function testDenormalizeUuid()
    {
        $uuid = Uuid::v4();
        $normalizer = new UuidNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization($uuid->__toString(), Uuid::class));
        $this->assertEquals($uuid, $normalizer->denormalize($uuid->__toString(), Uuid::class));
    }

    public function testNoSupportDenormalizeUuid()
    {
        $uuid = 'notanuuid';
        $normalizer = new UuidNormalizer();
        $this->assertFalse($normalizer->supportsDenormalization($uuid, ''));
    }

    public function testFailDenormalizeUuid()
    {
        $this->expectException(InvalidIdentifierException::class);

        $uuid = 'notanuuid';
        $normalizer = new UuidNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization($uuid, Uuid::class));
        $normalizer->denormalize($uuid, Uuid::class);
    }

    public function testDoNotSupportNotString()
    {
        $uuid = Uuid::v4();
        $normalizer = new UuidNormalizer();
        $this->assertFalse($normalizer->supportsDenormalization($uuid, Uuid::class));
    }
}
