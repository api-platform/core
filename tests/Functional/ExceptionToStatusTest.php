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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ErrorWithOverridenStatus;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5924\TooManyRequests;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyExceptionToStatus;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ExceptionToStatusTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyExceptionToStatus::class, ErrorWithOverridenStatus::class, TooManyRequests::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([DummyExceptionToStatus::class]);
    }

    public function testOperationExceptionToStatusMaps404(): void
    {
        self::createClient()->request('GET', '/dummy_exception_to_statuses/123', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testResourceExceptionToStatusMaps400(): void
    {
        self::createClient()->request('PUT', '/dummy_exception_to_statuses/123', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'black'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testFilterValidationExceptionMaps400(): void
    {
        self::createClient()->request('GET', '/dummy_exception_to_statuses', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testOverrideValidationExceptionStatusOnDelete(): void
    {
        self::createClient()->request('DELETE', '/error_with_overriden_status/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertJsonContains(['status' => 403]);
    }

    public function testHttpExceptionHeadersAreRetained(): void
    {
        self::createClient()->request('GET', '/issue5924', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(429);
        $this->assertResponseHeaderSame('retry-after', '32');
    }
}
