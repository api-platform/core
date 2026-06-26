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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\PostNoOutputResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * RFC 9110 §10.2.1: the Allow header must advertise only methods that are actually
 * valid for the target resource. HEAD is defined as GET-without-body (§9.3.2), so a
 * resource that declares no GET operation does not support HEAD — a real HEAD request
 * returns 405. The advertised Allow header must therefore not claim HEAD either.
 */
final class HeadAllowWithoutGetTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [PostNoOutputResource::class];
    }

    public function testHeadIsNotAdvertisedWithoutGetOperation(): void
    {
        $client = self::createClient();

        $client->request('HEAD', '/jsonld_post_no_output', ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseStatusCodeSame(405);

        $response = $client->request('POST', '/jsonld_post_no_output', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['lorem' => 'x'],
        ]);

        $headers = array_change_key_case($response->getHeaders(false));
        $this->assertArrayHasKey('allow', $headers);
        $this->assertStringNotContainsString('HEAD', $headers['allow'][0]);
    }
}
