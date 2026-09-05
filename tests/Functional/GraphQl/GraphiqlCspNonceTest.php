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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class GraphiqlCspNonceRequestListener
{
    public function __construct(private readonly string $nonce)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        $event->getRequest()->attributes->set('_csp_nonce', $this->nonce);
    }
}

final class GraphiqlCspNonceTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('csp_nonce', static fn (string $directive = 'script'): string => 'function-nonce-'.$directive),
        ];
    }
}

class GraphiqlCspNonceAppKernel extends \AppKernel
{
    public static bool $requestNonceEnabled = false;
    public static bool $cspNonceFunctionEnabled = false;

    private function suffix(): string
    {
        return (self::$requestNonceEnabled ? 'req_' : 'no_req_').(self::$cspNonceFunctionEnabled ? 'fn' : 'no_fn');
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/graphiql_csp_'.$this->suffix();
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/graphiql_csp_'.$this->suffix();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('api_platform', [
                'graphql' => ['enabled' => true, 'graphiql' => ['enabled' => true]],
            ]);

            if (GraphiqlCspNonceAppKernel::$requestNonceEnabled) {
                $container->register('test.csp_nonce_listener', GraphiqlCspNonceRequestListener::class)
                    ->setArguments(['request-nonce-123'])
                    ->setPublic(true)
                    ->addTag('kernel.event_listener', ['event' => KernelEvents::REQUEST, 'priority' => 256]);
            }

            if (GraphiqlCspNonceAppKernel::$cspNonceFunctionEnabled) {
                $container->register('test.csp_nonce_twig_extension', GraphiqlCspNonceTwigExtension::class)
                    ->setPublic(true)
                    ->addTag('twig.extension');
            }
        });
    }
}

final class GraphiqlCspNonceTest extends ApiTestCase
{
    protected static ?bool $alwaysBootKernel = true;

    protected static function getKernelClass(): string
    {
        return GraphiqlCspNonceAppKernel::class;
    }

    protected function tearDown(): void
    {
        GraphiqlCspNonceAppKernel::$requestNonceEnabled = false;
        GraphiqlCspNonceAppKernel::$cspNonceFunctionEnabled = false;

        parent::tearDown();
    }

    public function testRequestAttributeNonceIsEmittedOnScripts(): void
    {
        GraphiqlCspNonceAppKernel::$requestNonceEnabled = true;
        GraphiqlCspNonceAppKernel::$cspNonceFunctionEnabled = false;

        $client = self::createClient();
        $client->request('GET', '/graphql/graphiql', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('<script type="importmap" nonce="request-nonce-123">', $content);
        $this->assertStringContainsString('<script id="graphiql-data" type="application/json" nonce="request-nonce-123">', $content);
        $this->assertStringContainsString('init-graphiql.js" nonce="request-nonce-123"', $content);
    }

    public function testCspNonceFunctionIsEmittedOnScripts(): void
    {
        GraphiqlCspNonceAppKernel::$requestNonceEnabled = false;
        GraphiqlCspNonceAppKernel::$cspNonceFunctionEnabled = true;

        $client = self::createClient();
        $client->request('GET', '/graphql/graphiql', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('<script type="importmap" nonce="function-nonce-script">', $content);
        $this->assertStringContainsString('init-graphiql.js" nonce="function-nonce-script"', $content);
    }

    public function testRequestAttributeNonceTakesPrecedenceOverFunction(): void
    {
        GraphiqlCspNonceAppKernel::$requestNonceEnabled = true;
        GraphiqlCspNonceAppKernel::$cspNonceFunctionEnabled = true;

        $client = self::createClient();
        $client->request('GET', '/graphql/graphiql', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('nonce="request-nonce-123"', $content);
        $this->assertStringNotContainsString('nonce="function-nonce-script"', $content);
    }

    public function testNoNonceIsEmittedWhenNoMechanismAvailable(): void
    {
        GraphiqlCspNonceAppKernel::$requestNonceEnabled = false;
        GraphiqlCspNonceAppKernel::$cspNonceFunctionEnabled = false;

        $client = self::createClient();
        $client->request('GET', '/graphql/graphiql', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('init-graphiql.js', $content);
        $this->assertStringNotContainsString('nonce=', $content);
    }
}
