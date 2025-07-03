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

use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\XmlWithJsonError;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class ErrorTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Error::class, XmlWithJsonError::class];
    }

    #[DataProvider('formatsProvider')]
    public function testRetrieveError(string $format, string $status, $expected): void
    {
        self::createClient()->request('GET', '/errors/'.$status, ['headers' => ['accept' => $format]]);
        $this->assertJsonContains($expected);
    }

    public function testRetrieveErrorHtml(): void
    {
        $response = self::createClient()->request('GET', '/errors/403', ['headers' => ['accept' => 'text/html']]);
        $this->assertEquals('<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Error 403</title>
    </head>
    <body><h1>Error 403</h1>Forbidden</body>
</html>', $response->getContent());
    }

    public static function formatsProvider(): array
    {
        return [
            [
                'application/vnd.api+json',
                '401',
                [
                    'errors' => [['id' => '/errors/401', 'detail' => 'Unauthorized']],
                ],
            ],
            [
                'application/ld+json',
                '401',
                [
                    '@type' => 'hydra:Error',
                    'hydra:description' => 'Unauthorized',
                ],
            ],
            [
                'application/json',
                '401',
                [
                    'detail' => 'Unauthorized',
                ],
            ],
        ];
    }

    public function testJsonError(): void
    {
        self::createClient()->request('POST', '/xml_with_json_errors', [
            'headers' => ['content-type' => 'application/json'],
            'body' => '<xml></xml>',
        ]);

        $this->assertResponseStatusCodeSame(415);
        $this->assertJsonContains(['detail' => 'The content-type "application/json" is not supported. Supported MIME types are "application/xml".']);
    }

    public function testXmlError(): void
    {
        self::createClient()->request('GET', '/notfound', [
            'headers' => ['accept' => 'text/xml'],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/xml; charset=utf-8');

        self::createClient()->request('GET', '/notfound', [
            'headers' => ['accept' => 'application/xml'],
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/xml; charset=utf-8');
    }
}
