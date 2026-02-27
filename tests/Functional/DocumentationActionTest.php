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
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Maxence Castel <maxence.castel59@gmail.com>
 */
class DocumentationActionAppKernel extends \AppKernel
{
    public static bool $swaggerUiEnabled = true;
    public static bool $reDocEnabled = true;
    public static bool $docsEnabled = true;

    public function getCacheDir(): string
    {
        $suffix = (self::$swaggerUiEnabled ? 'ui_' : 'no_ui_').(self::$reDocEnabled ? 'redoc' : 'no_redoc').(self::$docsEnabled ? '' : '_no_docs');

        return parent::getCacheDir().'/'.$suffix;
    }

    public function getLogDir(): string
    {
        $suffix = (self::$swaggerUiEnabled ? 'ui_' : 'no_ui_').(self::$reDocEnabled ? 'redoc' : 'no_redoc').(self::$docsEnabled ? '' : '_no_docs');

        return parent::getLogDir().'/'.$suffix;
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container) {
            $container->loadFromExtension('api_platform', [
                'enable_swagger_ui' => DocumentationActionAppKernel::$swaggerUiEnabled,
                'enable_re_doc' => DocumentationActionAppKernel::$reDocEnabled,
                'enable_docs' => DocumentationActionAppKernel::$docsEnabled,
            ]);
        });
    }
}

final class DocumentationActionTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected static function getKernelClass(): string
    {
        return DocumentationActionAppKernel::class;
    }

    public function testHtmlDocumentationIsNotAccessibleWhenSwaggerUiAndReDocAreDisabled(): void
    {
        DocumentationActionAppKernel::$swaggerUiEnabled = false;
        DocumentationActionAppKernel::$reDocEnabled = false;

        $client = self::createClient();

        $container = static::getContainer();
        $this->assertFalse($container->getParameter('api_platform.enable_swagger_ui'));
        $this->assertFalse($container->getParameter('api_platform.enable_re_doc'));

        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('Swagger UI and ReDoc are disabled.', $client->getResponse()->getContent(false));
    }

    public function testJsonDocumentationIsAccessibleWhenSwaggerUiIsDisabled(): void
    {
        DocumentationActionAppKernel::$swaggerUiEnabled = false;
        DocumentationActionAppKernel::$reDocEnabled = false;

        $client = self::createClient();

        $container = static::getContainer();
        $this->assertFalse($container->getParameter('api_platform.enable_swagger_ui'));
        $this->assertFalse($container->getParameter('api_platform.enable_re_doc'));

        $client->request('GET', '/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['openapi' => '3.1.0']);
        $this->assertJsonContains(['info' => ['title' => 'My Dummy API']]);
    }

    public function testHtmlDocumentationIsAccessibleWhenReDocEnabledAndSwaggerUiDisabled(): void
    {
        DocumentationActionAppKernel::$swaggerUiEnabled = false;
        DocumentationActionAppKernel::$reDocEnabled = true;

        $client = self::createClient();

        $container = static::getContainer();
        $this->assertFalse($container->getParameter('api_platform.enable_swagger_ui'));
        $this->assertTrue($container->getParameter('api_platform.enable_re_doc'));

        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Swagger UI and ReDoc are disabled.', $client->getResponse()->getContent(false));
    }

    public function testHtmlDocumentationIsAccessibleWhenSwaggerUiEnabledAndReDocDisabled(): void
    {
        DocumentationActionAppKernel::$swaggerUiEnabled = true;
        DocumentationActionAppKernel::$reDocEnabled = false;

        $client = self::createClient();

        $container = static::getContainer();
        $this->assertTrue($container->getParameter('api_platform.enable_swagger_ui'));
        $this->assertFalse($container->getParameter('api_platform.enable_re_doc'));

        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Swagger UI and ReDoc are disabled.', $client->getResponse()->getContent(false));
    }

    public function testHtmlDocumentationIsAccessibleWhenSwaggerUiIsEnabled(): void
    {
        DocumentationActionAppKernel::$swaggerUiEnabled = true;
        DocumentationActionAppKernel::$reDocEnabled = true;

        $client = self::createClient();

        $container = static::getContainer();
        $this->assertTrue($container->getParameter('api_platform.enable_swagger_ui'));
        $this->assertTrue($container->getParameter('api_platform.enable_re_doc'));

        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);
        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Swagger UI and ReDoc are disabled.', $client->getResponse()->getContent(false));
    }

    public function testJsonDocumentationIsAccessibleWhenSwaggerUiIsEnabled(): void
    {
        DocumentationActionAppKernel::$swaggerUiEnabled = true;
        DocumentationActionAppKernel::$reDocEnabled = true;

        $client = self::createClient();

        $container = static::getContainer();
        $this->assertTrue($container->getParameter('api_platform.enable_swagger_ui'));
        $this->assertTrue($container->getParameter('api_platform.enable_re_doc'));

        $client->request('GET', '/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['openapi' => '3.1.0']);
        $this->assertJsonContains(['info' => ['title' => 'My Dummy API']]);
    }

    public function testEnableDocsFalseDisablesSwaggerUiAndReDoc(): void
    {
        DocumentationActionAppKernel::$swaggerUiEnabled = true;
        DocumentationActionAppKernel::$reDocEnabled = true;
        DocumentationActionAppKernel::$docsEnabled = false;

        $client = self::createClient();

        $container = static::getContainer();
        $this->assertFalse($container->getParameter('api_platform.enable_docs'));
        // enable_docs: false acts as a master switch, forcing these to false
        $this->assertFalse($container->getParameter('api_platform.enable_swagger_ui'));
        $this->assertFalse($container->getParameter('api_platform.enable_re_doc'));

        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);
        $this->assertResponseStatusCodeSame(404);
    }

}
