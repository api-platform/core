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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ThrowOnNotFound\Feeder;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ThrowOnNotFoundTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [Feeder::class];
    }

    public function testPostWithThrowOnNotFoundReturns404WhenProviderReturnsNull(): void
    {
        self::createClient()->request('POST', '/throw_on_not_found_feeders/42/feed', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'body' => '{}',
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPostDefaultDoesNotReturn404WhenProviderReturnsNull(): void
    {
        self::createClient()->request('POST', '/throw_on_not_found_feeders/42/feed_default', [
            'headers' => ['Content-Type' => 'application/ld+json', 'Accept' => 'application/ld+json'],
            'body' => '{}',
        ]);

        $this->assertNotSame(404, self::getClient()->getResponse()->getStatusCode());
    }
}
