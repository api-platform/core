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

namespace ApiPlatform\Tests\Functional\Json;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\User;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InputOutputTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [User::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([User::class]);
    }

    public function testPasswordResetRequest(): void
    {
        self::createClient()->request('POST', '/users_reset/password_reset_request', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => ['email' => 'user@example.com'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertJsonEquals(['emailSentAt' => '2019-07-05T15:44:00+00:00']);
    }

    public function testPasswordResetRequestForUnknownUser(): void
    {
        self::createClient()->request('POST', '/users_reset/password_reset_request', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => ['email' => 'does-not-exist@example.com'],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json');
        $this->assertJsonContains(['detail' => 'User does not exist.']);
    }
}
