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

namespace ApiPlatform\Tests\Functional\Security;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class SecurityHeadersTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Dummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([Dummy::class]);
    }

    public function testCollectionResponseIncludesSecurityHeaders(): void
    {
        self::createClient()->request('GET', '/dummies', ['headers' => ['Accept' => 'application/ld+json']]);

        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertResponseHeaderSame('x-content-type-options', 'nosniff');
        $this->assertResponseHeaderSame('x-frame-options', 'deny');
    }

    public function testDeserializationErrorResponseIncludesSecurityHeaders(): void
    {
        self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => '{"name": 1}',
            ],
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('x-content-type-options', 'nosniff');
        $this->assertResponseHeaderSame('x-frame-options', 'deny');
    }

    public function testValidationErrorResponseIncludesSecurityHeaders(): void
    {
        self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'application/ld+json'],
                'body' => '{"name": ""}',
            ],
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');
        $this->assertResponseHeaderSame('x-content-type-options', 'nosniff');
        $this->assertResponseHeaderSame('x-frame-options', 'deny');
    }
}
