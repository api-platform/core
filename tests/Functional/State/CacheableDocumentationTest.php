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

namespace ApiPlatform\Tests\Functional\State;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\CacheableDocumentationDummy;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CacheableDocumentationAppKernel extends \AppKernel
{
    public static bool $useSymfonyListeners = false;

    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/cache_doc_'.($this::$useSymfonyListeners ? 'listeners' : 'controller');
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/cache_doc_'.($this::$useSymfonyListeners ? 'listeners' : 'controller');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('api_platform', [
                'use_symfony_listeners' => CacheableDocumentationAppKernel::$useSymfonyListeners,
            ]);
        });
    }
}

final class CacheableDocumentationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = true;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CacheableDocumentationDummy::class];
    }

    protected static function getKernelClass(): string
    {
        return CacheableDocumentationAppKernel::class;
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function modeProvider(): iterable
    {
        yield 'controller mode' => [false];
        yield 'listener mode' => [true];
    }

    #[DataProvider('modeProvider')]
    public function testDocumentationResponseHasCacheHeaders(bool $useSymfonyListeners): void
    {
        CacheableDocumentationAppKernel::$useSymfonyListeners = $useSymfonyListeners;

        $response = self::createClient()->request('GET', '/docs.jsonld', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $headers = $response->getHeaders();
        $this->assertNotEmpty($headers['etag'][0] ?? null, 'documentation response is missing an ETag');
        $cacheControl = $headers['cache-control'][0] ?? '';
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    #[DataProvider('modeProvider')]
    public function testEntrypointResponseHasCacheHeaders(bool $useSymfonyListeners): void
    {
        CacheableDocumentationAppKernel::$useSymfonyListeners = $useSymfonyListeners;

        $response = self::createClient()->request('GET', '/index.jsonld', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $headers = $response->getHeaders();
        $this->assertNotEmpty($headers['etag'][0] ?? null, 'entrypoint response is missing an ETag');
        $cacheControl = $headers['cache-control'][0] ?? '';
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    #[DataProvider('modeProvider')]
    public function testDocumentationReturnsNotModifiedWhenIfNoneMatchMatches(bool $useSymfonyListeners): void
    {
        CacheableDocumentationAppKernel::$useSymfonyListeners = $useSymfonyListeners;

        $client = self::createClient();
        $first = $client->request('GET', '/docs.jsonld', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $etag = $first->getHeaders()['etag'][0] ?? null;
        $this->assertNotEmpty($etag, 'expected an ETag on the first documentation response');

        $client->request('GET', '/docs.jsonld', [
            'headers' => [
                'Accept' => 'application/ld+json',
                'If-None-Match' => $etag,
            ],
        ]);
        $this->assertResponseStatusCodeSame(304);
    }

    #[DataProvider('modeProvider')]
    public function testRegularResourceDoesNotHaveDocumentationCacheHeaders(bool $useSymfonyListeners): void
    {
        CacheableDocumentationAppKernel::$useSymfonyListeners = $useSymfonyListeners;

        $response = self::createClient()->request('GET', '/cacheable_documentation_dummies');

        $this->assertResponseStatusCodeSame(200);
        $headers = $response->getHeaders();
        $cacheControl = $headers['cache-control'][0] ?? '';
        $this->assertStringNotContainsString('must-revalidate', $cacheControl, 'regular resource must not be wrapped by the documentation cache decorator');
        $this->assertStringNotContainsString('max-age=0', $cacheControl, 'regular resource must not be wrapped by the documentation cache decorator');
    }
}
