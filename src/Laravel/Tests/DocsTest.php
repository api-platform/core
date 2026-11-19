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

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;

class DocsTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    public function testOpenApi(): void
    {
        $res = $this->get('/api/docs.jsonopenapi');
        $this->assertArrayHasKey('openapi', $res->json());
        $this->assertSame('application/vnd.openapi+json', $res->headers->get('content-type'));
    }

    public function testOpenApiAccept(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'application/vnd.openapi+json']);
        $this->assertArrayHasKey('openapi', $res->json());
        $this->assertSame('application/vnd.openapi+json', $res->headers->get('content-type'));
    }

    public function testOpenApiFilterTags(): void
    {
        $res = $this->get('/api/docs.jsonopenapi?filter_tags[]=internal');
        $paths = $res->json()['paths'];

        $this->assertArrayHasKey('/api/tagged_operations', $paths);
        $this->assertArrayNotHasKey('/api/tagged_operations/{id}', $paths);
    }

    public function testJsonLd(): void
    {
        $res = $this->get('/api/docs.jsonld');
        $this->assertArrayHasKey('@context', $res->json());
        $this->assertSame('application/ld+json', $res->headers->get('content-type'));
    }

    public function testOpenApiInfoWithoutContactAndLicense(): void
    {
        $info = $this->get('/api/docs.jsonopenapi')->json()['info'];

        $this->assertArrayNotHasKey('contact', $info);
        $this->assertArrayNotHasKey('license', $info);
    }

    /**
     * @param Application $app
     */
    protected function useContactAndLicense($app): void
    {
        $app['config']->set('api-platform.swagger_ui.contact', ['name' => 'API Support', 'url' => 'https://example.com/support', 'email' => 'support@example.com']);
        $app['config']->set('api-platform.swagger_ui.license', ['name' => 'MIT']);
    }

    #[DefineEnvironment('useContactAndLicense')]
    public function testOpenApiInfoWithContactAndLicense(): void
    {
        $info = $this->get('/api/docs.jsonopenapi')->json()['info'];

        $this->assertSame(['name' => 'API Support', 'url' => 'https://example.com/support', 'email' => 'support@example.com'], $info['contact']);
        $this->assertSame(['name' => 'MIT'], $info['license']);
    }

    public function testJsonLdAccept(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'application/ld+json']);
        $this->assertArrayHasKey('@context', $res->json());
        $this->assertSame('application/ld+json', $res->headers->get('content-type'));
    }

    public function testHtmlDocsRendersSwaggerUiByDefault(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'text/html']);
        $res->assertOk();
        $content = (string) $res->getContent();

        $this->assertStringContainsString('init-swagger-ui.js', $content);
        $this->assertStringContainsString('id="formats"', $content);
        $this->assertStringContainsString('>ReDoc</a>', $content);
        $this->assertStringContainsString('>Scalar</a>', $content);
        $this->assertStringNotContainsString('>Swagger UI</a>', $content);
    }

    public function testHtmlDocsRendersRedocWhenRequested(): void
    {
        $res = $this->get('/api/docs?ui=redoc', headers: ['accept' => 'text/html']);
        $res->assertOk();
        $content = (string) $res->getContent();

        $this->assertStringContainsString('init-redoc-ui.js', $content);
        $this->assertStringContainsString('id="formats"', $content);
        $this->assertStringContainsString('>Swagger UI</a>', $content);
        $this->assertStringContainsString('>Scalar</a>', $content);
        $this->assertStringNotContainsString('>ReDoc</a>', $content);
    }

    public function testHtmlDocsRendersScalarWithoutFooterWhenRequested(): void
    {
        $res = $this->get('/api/docs?ui=scalar', headers: ['accept' => 'text/html']);
        $res->assertOk();
        $content = (string) $res->getContent();

        $this->assertStringContainsString('init-scalar-ui.js', $content);
        $this->assertStringNotContainsString('id="formats"', $content);
    }

    public function testHtmlDocsHideGraphiqlLinkWhenGraphQlIsDisabled(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'text/html']);
        $res->assertOk();

        $this->assertStringNotContainsString('graphiql-link', (string) $res->getContent());
    }

    /**
     * @param Application $app
     */
    protected function useGraphQl($app): void
    {
        $app['config']->set('api-platform.graphql.enabled', true);
    }

    #[DefineEnvironment('useGraphQl')]
    public function testHtmlDocsShowGraphiqlLinkWhenGraphQlIsEnabled(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'text/html']);
        $res->assertOk();

        $this->assertStringContainsString('/api/graphiql" class="graphiql-link">GraphiQL</a>', (string) $res->getContent());
    }

    public function testSwaggerDataDoesNotContainWithCredentialsByDefault(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'text/html']);
        $this->assertStringNotContainsString('"withCredentials":true', (string) $res->getContent());
    }

    /**
     * @param Application $app
     */
    protected function usePersistAuthorization($app): void
    {
        $app['config']->set('api-platform.swagger_ui.persist_authorization', true);
    }

    #[DefineEnvironment('usePersistAuthorization')]
    public function testSwaggerDataContainsPersistAuthorization(): void
    {
        $res = $this->get('/api/docs', headers: ['accept' => 'text/html']);
        $this->assertStringContainsString('"persistAuthorization":true', (string) $res->getContent());
    }
}
