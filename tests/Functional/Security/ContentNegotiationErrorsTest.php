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

final class ContentNegotiationErrorsTest extends ApiTestCase
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

    public function testUnsupportedRequestContentTypeReturns415(): void
    {
        self::createClient()->request(
            'POST',
            '/dummies',
            [
                'headers' => ['Content-Type' => 'text/plain', 'Accept' => 'application/ld+json'],
                'body' => 'something',
            ],
        );

        $this->assertResponseStatusCodeSame(415);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
        $this->assertJsonContains([
            'detail' => 'The content-type "text/plain" is not supported. Supported MIME types are "application/ld+json", "application/hal+json", "application/vnd.api+json", "application/xml", "text/xml", "application/json", "text/html", "application/graphql", "multipart/form-data".',
        ]);
    }

    public function testUnsupportedAcceptHeaderReturns406(): void
    {
        self::createClient()->request('GET', '/dummies', ['headers' => ['Accept' => 'text/plain']]);

        $this->assertResponseStatusCodeSame(406);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
        $this->assertJsonContains([
            'detail' => 'Requested format "text/plain" is not supported. Supported MIME types are "application/ld+json", "application/hal+json", "application/vnd.api+json", "application/xml", "text/xml", "application/json", "text/html", "application/graphql", "multipart/form-data".',
        ]);
    }

    public function testAcceptHeaderDifferentFromUrlFormatReturns406(): void
    {
        self::createClient()->request('GET', '/dummies/1.json', ['headers' => ['Accept' => 'text/xml']]);

        $this->assertResponseStatusCodeSame(406);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
        $this->assertJsonContains([
            'detail' => 'Requested format "text/xml" is not supported. Supported MIME types are "application/json".',
        ]);
    }

    public function testInvalidAcceptHeaderReturns406(): void
    {
        self::createClient()->request('GET', '/dummies/1', ['headers' => ['Accept' => 'invalid']]);

        $this->assertResponseStatusCodeSame(406);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
        $this->assertJsonContains([
            'detail' => 'Requested format "invalid" is not supported. Supported MIME types are "application/ld+json", "application/hal+json", "application/vnd.api+json", "application/xml", "text/xml", "application/json", "text/html", "application/graphql", "multipart/form-data".',
        ]);
    }

    public function testInvalidUrlFormatReturns404(): void
    {
        self::createClient()->request('GET', '/dummies/1.invalid');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
        $this->assertJsonContains([
            'detail' => 'Format "invalid" is not supported',
        ]);
    }

    public function testInvalidUrlFormatAndAcceptReturns404(): void
    {
        self::createClient()->request('GET', '/dummies/1.invalid', ['headers' => ['Accept' => 'text/invalid']]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
        $this->assertJsonContains([
            'detail' => 'Format "invalid" is not supported',
        ]);
    }
}
