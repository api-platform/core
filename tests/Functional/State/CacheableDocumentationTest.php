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

    /**
     * @var array<string, mixed>
     */
    public static array $cacheHeaders = [];

    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/cache_doc_'.($this::$useSymfonyListeners ? 'listeners' : 'controller').'_'.self::cacheHeadersSignature();
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/cache_doc_'.($this::$useSymfonyListeners ? 'listeners' : 'controller').'_'.self::cacheHeadersSignature();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        parent::configureContainer($c, $loader);

        $loader->load(static function (ContainerBuilder $container): void {
            $extensionConfig = [
                'use_symfony_listeners' => CacheableDocumentationAppKernel::$useSymfonyListeners,
            ];

            if ([] !== CacheableDocumentationAppKernel::$cacheHeaders) {
                $extensionConfig['documentation'] = ['cache_headers' => CacheableDocumentationAppKernel::$cacheHeaders];
            }

            $container->loadFromExtension('api_platform', $extensionConfig);
        });
    }

    private static function cacheHeadersSignature(): string
    {
        return [] === self::$cacheHeaders ? 'default' : substr(md5(serialize(self::$cacheHeaders)), 0, 8);
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

    protected function setUp(): void
    {
        parent::setUp();

        // reset config overrides so each test starts from defaults
        CacheableDocumentationAppKernel::$cacheHeaders = [];
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

    #[DataProvider('modeProvider')]
    public function testCustomMaxAgeAndSharedMaxAgeAreApplied(bool $useSymfonyListeners): void
    {
        CacheableDocumentationAppKernel::$useSymfonyListeners = $useSymfonyListeners;
        CacheableDocumentationAppKernel::$cacheHeaders = ['max_age' => 3600, 'shared_max_age' => 600];

        $response = self::createClient()->request('GET', '/docs.jsonld', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $cacheControl = $response->getHeaders()['cache-control'][0] ?? '';
        $this->assertStringContainsString('max-age=3600', $cacheControl);
        $this->assertStringContainsString('s-maxage=600', $cacheControl);
    }

    #[DataProvider('modeProvider')]
    public function testMustRevalidateCanBeDisabled(bool $useSymfonyListeners): void
    {
        CacheableDocumentationAppKernel::$useSymfonyListeners = $useSymfonyListeners;
        CacheableDocumentationAppKernel::$cacheHeaders = ['must_revalidate' => false];

        $response = self::createClient()->request('GET', '/docs.jsonld', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $cacheControl = $response->getHeaders()['cache-control'][0] ?? '';
        $this->assertStringNotContainsString('must-revalidate', $cacheControl);
    }

    #[DataProvider('modeProvider')]
    public function testEtagCanBeDisabled(bool $useSymfonyListeners): void
    {
        CacheableDocumentationAppKernel::$useSymfonyListeners = $useSymfonyListeners;
        CacheableDocumentationAppKernel::$cacheHeaders = ['etag' => false];

        $response = self::createClient()->request('GET', '/docs.jsonld', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        // documentation decorator is still wired (must-revalidate proves it) but it must not have set its md5 ETag
        $cacheControl = $response->getHeaders()['cache-control'][0] ?? '';
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $etag = trim($response->getHeaders()['etag'][0] ?? '', '"');
        $this->assertFalse(1 === preg_match('/^[a-f0-9]{32}$/', $etag), 'documentation decorator must not produce its md5 ETag when etag is disabled');
    }

    #[DataProvider('modeProvider')]
    public function testFeatureCanBeDisabled(bool $useSymfonyListeners): void
    {
        CacheableDocumentationAppKernel::$useSymfonyListeners = $useSymfonyListeners;
        CacheableDocumentationAppKernel::$cacheHeaders = ['enabled' => false];

        $response = self::createClient()->request('GET', '/docs.jsonld', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $cacheControl = $response->getHeaders()['cache-control'][0] ?? '';
        $this->assertStringNotContainsString('must-revalidate', $cacheControl, 'when disabled, the cache decorator must not be wired');
    }
}
