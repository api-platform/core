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

class SwaggerUiCspNonceAppKernel extends \AppKernel
{
    public static bool $requestNonceEnabled = false;
    public static bool $cspNonceFunctionEnabled = false;

    private function suffix(): string
    {
        return (self::$requestNonceEnabled ? 'req_' : 'no_req_').(self::$cspNonceFunctionEnabled ? 'fn' : 'no_fn');
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

            if (SwaggerUiCspNonceAppKernel::$cspNonceFunctionEnabled) {
                $container->register('test.csp_nonce_twig_extension', CspNonceTwigExtension::class)
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
        SwaggerUiCspNonceAppKernel::$cspNonceFunctionEnabled = false;

        parent::tearDown();
    }

    public function testRequestAttributeNonceIsEmittedOnScripts(): void
    {
        SwaggerUiCspNonceAppKernel::$requestNonceEnabled = true;
        SwaggerUiCspNonceAppKernel::$cspNonceFunctionEnabled = false;

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
        SwaggerUiCspNonceAppKernel::$cspNonceFunctionEnabled = true;

        $client = self::createClient();
        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('nonce="function-nonce-script"', $content);
        $this->assertStringContainsString('swagger-ui-bundle.js" nonce="function-nonce-script"', $content);
    }

    public function testRequestAttributeNonceTakesPrecedenceOverFunction(): void
    {
        SwaggerUiCspNonceAppKernel::$requestNonceEnabled = true;
        SwaggerUiCspNonceAppKernel::$cspNonceFunctionEnabled = true;

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
        SwaggerUiCspNonceAppKernel::$cspNonceFunctionEnabled = false;

        $client = self::createClient();
        $client->request('GET', '/docs', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('swagger-ui-bundle.js', $content);
        $this->assertStringNotContainsString('nonce=', $content);
    }
}
