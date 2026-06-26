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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\HeadSpyResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerHelper;
use Symfony\Component\JsonStreamer\JsonStreamWriter;

/**
 * On a HEAD request, API Platform must skip body construction so that the (lazy)
 * collection is never iterated: zero row SELECT. The spy paginator throws on
 * getIterator()/count(); a HEAD that does not throw proves no iteration occurred.
 */
final class HeadRequestTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [HeadSpyResource::class];
    }

    public function testHeadDoesNotIterateCollection(): void
    {
        $response = self::createClient()->request('HEAD', '/head_spy_resources', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertEmpty($response->getContent(false));

        $headers = array_change_key_case($response->getHeaders(false));
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertStringStartsWith('application/ld+json', $headers['content-type'][0]);
        $this->assertArrayHasKey('vary', $headers);
        $this->assertStringContainsString('Accept', $headers['vary'][0]);
    }

    public function testGetIteratesCollection(): void
    {
        self::createClient()->request('GET', '/head_spy_resources', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(418);
    }

    public function testOptionsIsUnaffected(): void
    {
        $response = self::createClient()->request('OPTIONS', '/head_spy_resources', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $headers = array_change_key_case($response->getHeaders(false));
        $this->assertArrayHasKey('allow', $headers);
        $this->assertStringContainsString('GET', $headers['allow'][0]);
    }

    public function testHeadDoesNotIterateJsonStreamCollection(): void
    {
        if (false === (class_exists(ControllerHelper::class) && class_exists(JsonStreamWriter::class))) {
            $this->markTestSkipped('JsonStreamer component not installed.');
        }

        $response = self::createClient()->request('HEAD', '/head_spy_stream_resources', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertEmpty($response->getContent(false));

        $headers = array_change_key_case($response->getHeaders(false));
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertArrayHasKey('vary', $headers);
        $this->assertStringContainsString('Accept', $headers['vary'][0]);
    }
}
