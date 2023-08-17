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

namespace ApiPlatform\RamseyUuid\Tests\Serializer;

use ApiPlatform\RamseyUuid\Serializer\UuidDenormalizer;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

class UuidDenormalizerTest extends TestCase
{
    public function testDenormalizeUuid(): void
    {
        $uuid = Uuid::uuid4();
        $normalizer = new UuidDenormalizer();
        self::assertTrue($normalizer->supportsDenormalization($uuid->toString(), Uuid::class));
        self::assertEquals($uuid, $normalizer->denormalize($uuid->toString(), Uuid::class));
    }

    public function testNoSupportDenormalizeUuid(): void
    {
        $uuid = 'notanuuid';
        $normalizer = new UuidDenormalizer();
        self::assertFalse($normalizer->supportsDenormalization($uuid, ''));
    }

    public function testFailDenormalizeUuid(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $uuid = 'notanuuid';
        $normalizer = new UuidDenormalizer();
        $normalizer->denormalize($uuid, Uuid::class);
    }

    public function testDoNotSupportNotString(): void
    {
        $uuid = Uuid::uuid4();
        $normalizer = new UuidDenormalizer();
        self::assertFalse($normalizer->supportsDenormalization($uuid, Uuid::class));
    }
}
