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

use ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UlidNormalizer;
use ApiPlatform\Exception\InvalidIdentifierException;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Ulid;

/**
 * @group legacy
 */
final class UlidNormalizerTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        if (!class_exists(AbstractUid::class)) {
            $this->markTestSkipped();
        }
    }

    public function testDenormalizeUlid()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UlidNormalizer" will be replaced by "ApiPlatform\Symfony\UriVariableTransformer\UlidUriVariableTransformer".');
        $ulid = new Ulid();
        $normalizer = new UlidNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization($ulid->__toString(), Ulid::class));
        $this->assertEquals($ulid, $normalizer->denormalize($ulid->__toString(), Ulid::class));
    }

    public function testNoSupportDenormalizeUlid()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UlidNormalizer" will be replaced by "ApiPlatform\Symfony\UriVariableTransformer\UlidUriVariableTransformer".');
        $ulid = 'notanulid';
        $normalizer = new UlidNormalizer();
        $this->assertFalse($normalizer->supportsDenormalization($ulid, ''));
    }

    public function testFailDenormalizeUlid()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UlidNormalizer" will be replaced by "ApiPlatform\Symfony\UriVariableTransformer\UlidUriVariableTransformer".');
        $this->expectException(InvalidIdentifierException::class);

        $ulid = 'notanulid';
        $normalizer = new UlidNormalizer();
        $this->assertTrue($normalizer->supportsDenormalization($ulid, Ulid::class));
        $normalizer->denormalize($ulid, Ulid::class);
    }

    public function testDoNotSupportNotString()
    {
        $this->expectDeprecation('Since api-platform/core 2.7: The class "ApiPlatform\Core\Bridge\Symfony\Identifier\Normalizer\UlidNormalizer" will be replaced by "ApiPlatform\Symfony\UriVariableTransformer\UlidUriVariableTransformer".');
        $ulid = new Ulid();
        $normalizer = new UlidNormalizer();
        $this->assertFalse($normalizer->supportsDenormalization($ulid, Ulid::class));
    }
}
