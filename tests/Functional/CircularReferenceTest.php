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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CircularReference;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CircularReferenceTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CircularReference::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema($this->getResources());
    }

    public function testSelfReferencingCircularReference(): void
    {
        $client = self::createClient();
        $headers = ['Content-Type' => 'application/ld+json'];

        $client->request('POST', '/circular_references', ['headers' => $headers, 'json' => new \stdClass()]);
        $client->request('PUT', '/circular_references/1', [
            'headers' => $headers,
            'json' => ['parent' => '/circular_references/1'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/CircularReference',
            '@id' => '/circular_references/1',
            '@type' => 'CircularReference',
            'parent' => '/circular_references/1',
            'children' => ['/circular_references/1'],
        ]);
    }

    public function testFetchCircularReferenceWithParentSibling(): void
    {
        $client = self::createClient();
        $headers = ['Content-Type' => 'application/ld+json'];

        $client->request('POST', '/circular_references', ['headers' => $headers, 'json' => new \stdClass()]);
        $client->request('POST', '/circular_references', ['headers' => $headers, 'json' => new \stdClass()]);
        $client->request('PUT', '/circular_references/1', [
            'headers' => $headers,
            'json' => ['parent' => '/circular_references/1'],
        ]);
        $client->request('PUT', '/circular_references/2', [
            'headers' => $headers,
            'json' => ['parent' => '/circular_references/1'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/CircularReference',
            '@id' => '/circular_references/2',
            '@type' => 'CircularReference',
            'parent' => [
                '@id' => '/circular_references/1',
                '@type' => 'CircularReference',
                'parent' => '/circular_references/1',
                'children' => ['/circular_references/1', '/circular_references/2'],
            ],
            'children' => [],
        ]);

        $client->request('GET', '/circular_references/1');
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/CircularReference',
            '@id' => '/circular_references/1',
            '@type' => 'CircularReference',
            'parent' => '/circular_references/1',
            'children' => [
                '/circular_references/1',
                [
                    '@id' => '/circular_references/2',
                    '@type' => 'CircularReference',
                    'parent' => '/circular_references/1',
                    'children' => [],
                ],
            ],
        ]);
    }
}
