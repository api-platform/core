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
use ApiPlatform\Symfony\Bundle\Test\Client;

class DocumentationActionTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    private function createClientWithEnv(string $env): Client
    {
        return self::createClient(['environment' => $env]);
    }

    public function testHtmlDocumentationIsNotAccessibleWhenSwaggerUiIsDisabled(): void
    {
        $client = $this->createClientWithEnv('swagger_ui_disabled');

        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Swagger UI is disabled.', $client->getResponse()->getContent(false));
    }

    public function testJsonDocumentationIsAccessibleWhenSwaggerUiIsDisabled(): void
    {
        $client = $this->createClientWithEnv('swagger_ui_disabled');

        $client->request('GET', '/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['openapi' => '3.1.0']);
        $this->assertJsonContains(['info' => ['title' => 'My Dummy API']]);
    }

    public function testHtmlDocumentationIsAccessibleWhenSwaggerUiIsEnabled(): void
    {
        $client = $this->createClientWithEnv('swagger_ui_enabled');

        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Swagger UI is disabled.', $client->getResponse()->getContent(false));
    }

    public function testJsonDocumentationIsAccessibleWhenSwaggerUiIsEnabled(): void
    {
        $client = $this->createClientWithEnv('swagger_ui_enabled');

        $client->request('GET', '/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['openapi' => '3.1.0']);
        $this->assertJsonContains(['info' => ['title' => 'My Dummy API']]);
    }
}
