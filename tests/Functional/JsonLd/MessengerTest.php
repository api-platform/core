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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MessengerWithInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MessengerWithResponse;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MessengerTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [MessengerWithInput::class, MessengerWithResponse::class];
    }

    public function testPostMessengerWithSynchronousResultReturnsLdPayload(): void
    {
        $response = self::createClient()->request('POST', '/messenger_with_inputs', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['var' => 'test'],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $body = $response->toArray();
        $this->assertSame('/contexts/MessengerWithInput', $body['@context']);
        $this->assertSame('/messenger_with_inputs/1', $body['@id']);
        $this->assertSame('MessengerWithInput', $body['@type']);
        $this->assertSame(1, $body['id']);
        $this->assertSame('test', $body['name']);
    }

    public function testPostMessengerWithResponseHandlerReturnsRawResponse(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('POST', '/messenger_with_responses', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ],
            'json' => ['var' => 'test'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertSame(['data' => 123], json_decode($response->getContent(), true));
    }

    private function isMongoDB(): bool
    {
        return 'mongodb' === static::getContainer()->getParameter('kernel.environment');
    }
}
