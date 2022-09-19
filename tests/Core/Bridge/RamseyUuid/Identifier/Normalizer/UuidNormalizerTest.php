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

namespace ApiPlatform\Core\Tests\Bridge\RamseyUuid\Identifier\Normalizer;

use ApiPlatform\Core\Bridge\RamseyUuid\Identifier\Normalizer\UuidNormalizer;
use ApiPlatform\Exception\InvalidIdentifierException;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * @group legacy
 */
class UuidNormalizerTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testDenormalizeUuid()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\RamseyUuid\Identifier\Normalizer\UuidNormalizer" will be replaced by "ApiPlatform\RamseyUuid\UriVariableTransformer\UuidUriVariableTransformer".');
        $uuid = Uuid::uuid4();
        $normalizer = new UuidNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization($uuid->toString(), Uuid::class));
        $this->assertEquals($uuid, $normalizer->denormalize($uuid->toString(), Uuid::class));
    }

    public function testNoSupportDenormalizeUuid()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\RamseyUuid\Identifier\Normalizer\UuidNormalizer" will be replaced by "ApiPlatform\RamseyUuid\UriVariableTransformer\UuidUriVariableTransformer".');
        $uuid = 'notanuuid';
        $normalizer = new UuidNormalizer();
        $this->assertFalse($normalizer->supportsDenormalization($uuid, ''));
    }

    public function testFailDenormalizeUuid()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\RamseyUuid\Identifier\Normalizer\UuidNormalizer" will be replaced by "ApiPlatform\RamseyUuid\UriVariableTransformer\UuidUriVariableTransformer".');
        $this->expectException(InvalidIdentifierException::class);

        $uuid = 'notanuuid';
        $normalizer = new UuidNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization($uuid, Uuid::class));
        $normalizer->denormalize($uuid, Uuid::class);
    }

    public function testDoNotSupportNotString()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\RamseyUuid\Identifier\Normalizer\UuidNormalizer" will be replaced by "ApiPlatform\RamseyUuid\UriVariableTransformer\UuidUriVariableTransformer".');
        $uuid = Uuid::uuid4();
        $normalizer = new UuidNormalizer();
        $this->assertFalse($normalizer->supportsDenormalization($uuid, Uuid::class));
    }
}
