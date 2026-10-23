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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7915\IrisDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * The "api_platform_iris" route loader registers item routes answered by the not exposed action.
 */
final class IrisRoutesTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [IrisDummy::class];
    }

    public function testNotExposedItemRouteIsAJsonLdNotFound(): void
    {
        $this->bootIrisKernel();
        self::createClient()->request('GET', '/iris_dummies/1', ['headers' => ['accept' => 'application/ld+json']]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains([
            '@context' => '/contexts/Error',
            'status' => 404,
        ]);
    }

    private function bootIrisKernel(): void
    {
        $baseEnv = $_SERVER['APP_ENV'] ?? 'test';
        if ('mongodb' === $baseEnv) {
            $this->markTestSkipped('The iris environment is not configured for MongoDB.');
        }

        // AppKernel overrides environment with $_SERVER['APP_ENV'] (behat compat).
        $_SERVER['APP_ENV'] = 'iris';

        try {
            self::bootKernel(['environment' => 'iris']);
        } finally {
            $_SERVER['APP_ENV'] = $baseEnv;
        }
    }
}
