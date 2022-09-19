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
use ApiPlatform\Exception\InvalidIdentifierException;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid;

/**
 * @group legacy
 */
final class UuidNormalizerTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        if (!class_exists(AbstractUid::class)) {
            $this->markTestSkipped();
        }
    }

    public function testDenormalizeUuid()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UuidNormalizer" will be replaced by "ApiPlatform\Symfony\UriVariableTransformer\UuidUriVariableTransformer".');

        $uuid = Uuid::v4();
        $normalizer = new UuidNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization($uuid->__toString(), Uuid::class));
        $this->assertEquals($uuid, $normalizer->denormalize($uuid->__toString(), Uuid::class));
    }

    public function testNoSupportDenormalizeUuid()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UuidNormalizer" will be replaced by "ApiPlatform\Symfony\UriVariableTransformer\UuidUriVariableTransformer".');
        $uuid = 'notanuuid';
        $normalizer = new UuidNormalizer();
        $this->assertFalse($normalizer->supportsDenormalization($uuid, ''));
    }

    public function testFailDenormalizeUuid()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UuidNormalizer" will be replaced by "ApiPlatform\Symfony\UriVariableTransformer\UuidUriVariableTransformer".');
        $this->expectException(InvalidIdentifierException::class);

        $uuid = 'notanuuid';
        $normalizer = new UuidNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization($uuid, Uuid::class));
        $normalizer->denormalize($uuid, Uuid::class);
    }

    public function testDoNotSupportNotString()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UuidNormalizer" will be replaced by "ApiPlatform\Symfony\UriVariableTransformer\UuidUriVariableTransformer".');
        $uuid = Uuid::v4();
        $normalizer = new UuidNormalizer();
        $this->assertFalse($normalizer->supportsDenormalization($uuid, Uuid::class));
    }
}
