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

use ApiPlatform\Test\ApiTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CspNonceRequestListener
{
    public function __construct(private readonly string $nonce)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        $event->getRequest()->attributes->set('_csp_nonce', $this->nonce);
    }
}

final class CspNonceTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', static fn (string $directive = 'script'): string => 'function-nonce-'.$directive),
        ];
    }
}

final class CspNonceRuntime
{
    public function getCSPNonce(string $directive = 'script'): string
    {
        return 'runtime-nonce-'.$directive;
    }
}

final class CspNonceRuntimeTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        // Registered like NelmioSecurityBundle's csp_nonce: a Twig runtime function whose callable
        // is [RuntimeClass, 'method'] and must be resolved through the runtime loader.
        return [
            new TwigFunction('csp_nonce', [CspNonceRuntime::class, 'getCSPNonce']),
        ];
    }
}

class SwaggerUiCspNonceAppKernel extends \AppKernel
{
    public static bool $requestNonceEnabled = false;
    public static string $cspNonceMode = 'none'; // none|function|runtime

    private function suffix(): string
    {
        return (self::$requestNonceEnabled ? 'req_' : 'no_req_').self::$cspNonceMode;
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/csp_'.$this->suffix();
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/csp_'.$this->suffix();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('api_platform', [
                'enable_swagger_ui' => true,
                'enable_re_doc' => false,
                'enable_scalar' => false,
            ]);

            if (SwaggerUiCspNonceAppKernel::$requestNonceEnabled) {
                $container->register('test.csp_nonce_listener', CspNonceRequestListener::class)
                    ->setArguments(['request-nonce-123'])
                    ->setPublic(true)
                    ->addTag('kernel.event_listener', ['event' => KernelEvents::REQUEST, 'priority' => 256]);
            }

            if ('function' === SwaggerUiCspNonceAppKernel::$cspNonceMode) {
                $container->register('test.csp_nonce_twig_extension', CspNonceTwigExtension::class)
                    ->setPublic(true)
                    ->addTag('twig.extension');
            }

            if ('runtime' === SwaggerUiCspNonceAppKernel::$cspNonceMode) {
                $container->register(CspNonceRuntime::class, CspNonceRuntime::class)
                    ->setPublic(true)
                    ->addTag('twig.runtime');
                $container->register('test.csp_nonce_runtime_twig_extension', CspNonceRuntimeTwigExtension::class)
                    ->setPublic(true)
                    ->addTag('twig.extension');
            }
        });
    }
}

final class SwaggerUiCspNonceTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected static function getKernelClass(): string
    {
        return SwaggerUiCspNonceAppKernel::class;
    }

    protected function tearDown(): void
    {
        SwaggerUiCspNonceAppKernel::$requestNonceEnabled = false;
        SwaggerUiCspNonceAppKernel::$cspNonceMode = 'none';

        parent::tearDown();
    }

    public function testRequestAttributeNonceIsEmittedOnScripts(): void
    {
        SwaggerUiCspNonceAppKernel::$requestNonceEnabled = true;
        SwaggerUiCspNonceAppKernel::$cspNonceMode = 'none';

        $client = self::createClient();
        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('<script id="swagger-data" type="application/json" nonce="request-nonce-123">', $content);
        $this->assertStringContainsString('swagger-ui-bundle.js" nonce="request-nonce-123"', $content);
        $this->assertStringContainsString('init-common-ui.js', $content);
        $this->assertStringContainsString('defer nonce="request-nonce-123"', $content);
    }

    public function testCspNonceFunctionIsEmittedOnScripts(): void
    {
        SwaggerUiCspNonceAppKernel::$requestNonceEnabled = false;
        SwaggerUiCspNonceAppKernel::$cspNonceMode = 'function';

        $client = self::createClient();
        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('nonce="function-nonce-script"', $content);
        $this->assertStringContainsString('swagger-ui-bundle.js" nonce="function-nonce-script"', $content);
    }

    public function testCspNonceRuntimeFunctionIsEmittedOnScripts(): void
    {
        SwaggerUiCspNonceAppKernel::$requestNonceEnabled = false;
        SwaggerUiCspNonceAppKernel::$cspNonceMode = 'runtime';

        $client = self::createClient();
        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('nonce="runtime-nonce-script"', $content);
        $this->assertStringContainsString('swagger-ui-bundle.js" nonce="runtime-nonce-script"', $content);
    }

    public function testRequestAttributeNonceTakesPrecedenceOverFunction(): void
    {
        SwaggerUiCspNonceAppKernel::$requestNonceEnabled = true;
        SwaggerUiCspNonceAppKernel::$cspNonceMode = 'function';

        $client = self::createClient();
        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('nonce="request-nonce-123"', $content);
        $this->assertStringNotContainsString('nonce="function-nonce-script"', $content);
    }

    public function testNoNonceIsEmittedWhenNoMechanismAvailable(): void
    {
        SwaggerUiCspNonceAppKernel::$requestNonceEnabled = false;
        SwaggerUiCspNonceAppKernel::$cspNonceMode = 'none';

        $client = self::createClient();
        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('swagger-ui-bundle.js', $content);
        $this->assertStringNotContainsString('nonce=', $content);
    }
}
