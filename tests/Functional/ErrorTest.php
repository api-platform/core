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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DataUriFileUpload\FileUploadResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ErrorResourceWithGroups\Error as ErrorResourceWithGroupsError;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ErrorResourceWithGroups\ThrowsAnExceptionWithGroup;
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
        return [Error::class, XmlWithJsonError::class, ThrowsAnExceptionWithGroup::class, ErrorResourceWithGroupsError::class, FileUploadResource::class];
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

    public function testErrorResourceThrownFromProcessorRespectsGroups(): void
    {
        $response = self::createClient()->request('POST', '/error_resource_with_groups', ['json' => []]);
        $this->assertEquals('This should be returned in the response.', $response->toArray(false)['detail'] ?? false);
    }

    public function testDataUriExceptionMessageIsNotWrapped(): void
    {
        $response = self::createClient()->request('POST', '/data_uri_file_uploads', [
            'json' => [
                'file' => 'data:invalid',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json; charset=utf-8');

        $responseData = $response->toArray(false);

        $this->assertStringContainsString('The provided "data:" URI is not valid', $responseData['hydra:description'] ?? $responseData['detail']);
        $this->assertStringNotContainsString('The type of the "file" attribute must be', $responseData['hydra:description'] ?? $responseData['detail']);
    }
}
