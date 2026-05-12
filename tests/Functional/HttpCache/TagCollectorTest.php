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

namespace ApiPlatform\Tests\Functional\HttpCache;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ExtraPropertiesOnProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Relation2;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Relation3;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationEmbedder;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\HttpCache\TagCollectorCustom;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class TagCollectorTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            RelationEmbedder::class,
            RelatedDummy::class,
            ThirdLevel::class,
            ExtraPropertiesOnProperty::class,
            Relation2::class,
            Relation3::class,
        ];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('Custom tag collector is only enabled on SQLite test suite');
        }

        // Force a fresh kernel so the custom collector replacement is in effect
        // before any service that depends on it is instantiated.
        static::ensureKernelShutdown();
        self::bootKernel();
        $container = static::getContainer();
        $container->set(
            'api_platform.http_cache.tag_collector',
            new TagCollectorCustom($container->get('api_platform.iri_converter')),
        );

        $this->recreateSchema($this->getResources());
    }

    /**
     * Returns a client that keeps the kernel alive between HTTP requests so the
     * tag_collector override registered in setUp survives across calls.
     */
    private function disableRebootClient(): \ApiPlatform\Symfony\Bundle\Test\Client
    {
        $client = self::createClient();
        $client->getKernelBrowser()->disableReboot();

        return $client;
    }

    public function testCustomTagsOnEmptyResource(): void
    {
        $this->disableRebootClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => new \stdClass(),
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseNotHasHeader('Cache-Tags');

        $this->disableRebootClient()->request('GET', '/relation_embedders/1');
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Cache-Tags', '/RE/1#anotherRelated,/RE/1#related,/RE/1');
    }

    public function testCustomTagsForEmbeddedRelationJsonLd(): void
    {
        $this->disableRebootClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['anotherRelated' => ['name' => 'Related']],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $this->disableRebootClient()->request('GET', '/relation_embedders/1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame(
            'Cache-Tags',
            '/related_dummies/1#thirdLevel,/related_dummies/1,/RE/1#anotherRelated,/RE/1#related,/RE/1',
        );
        $this->assertJsonContains([
            '@context' => '/contexts/RelationEmbedder',
            '@id' => '/relation_embedders/1',
            '@type' => 'RelationEmbedder',
            'krondstadt' => 'Krondstadt',
            'anotherRelated' => [
                '@id' => '/related_dummies/1',
                '@type' => 'https://schema.org/Product',
                'symfony' => 'symfony',
                'thirdLevel' => null,
            ],
            'related' => null,
        ]);
    }

    public function testCustomTagsForEmbeddedRelationHal(): void
    {
        $this->disableRebootClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['anotherRelated' => ['name' => 'Related']],
        ]);

        $this->disableRebootClient()->request('GET', '/relation_embedders/1', [
            'headers' => ['Accept' => 'application/hal+json'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame(
            'Cache-Tags',
            '/RE/1,/related_dummies/1,/related_dummies/1#thirdLevel,/RE/1#anotherRelated,/RE/1#related',
        );
        $this->assertJsonContains([
            '_embedded' => [
                'anotherRelated' => [
                    '_links' => ['self' => ['href' => '/related_dummies/1']],
                ],
            ],
        ]);
    }

    public function testCustomTagsForEmbeddedRelationJsonApi(): void
    {
        $this->disableRebootClient()->request('POST', '/relation_embedders', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['anotherRelated' => ['name' => 'Related']],
        ]);

        $this->disableRebootClient()->request('GET', '/relation_embedders/1', [
            'headers' => ['Accept' => 'application/vnd.api+json'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame(
            'Cache-Tags',
            '/RE/1,/RE/1#anotherRelated,/RE/1#related',
        );
        $this->assertJsonContains([
            'data' => [
                'relationships' => [
                    'anotherRelated' => [
                        'data' => ['type' => 'RelatedDummy', 'id' => '/related_dummies/1'],
                    ],
                ],
            ],
        ]);
    }

    public function testCustomTagsFromApiPropertyExtraProperties(): void
    {
        $this->disableRebootClient()->request('POST', '/extra_properties_on_properties', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => new \stdClass(),
        ]);
        $this->assertResponseStatusCodeSame(201);

        $this->disableRebootClient()->request('GET', '/extra_properties_on_properties/1');
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame(
            'Cache-Tags',
            '/extra_properties_on_properties/1#overrideRelationTag,/extra_properties_on_properties/1',
        );
    }

    /**
     * Replaces the three "Get a Relation3 (test collection of links; ...)" behat
     * scenarios. Each format asserts the same Cache-Tags set because the
     * resource collection only contains link-only Relation2 references.
     */
    public function testCustomTagsForManyToManyCollections(): void
    {
        $client = $this->disableRebootClient();
        $client->request('POST', '/relation2s', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => new \stdClass(),
        ]);
        $client->request('POST', '/relation2s', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => new \stdClass(),
        ]);
        $client->request('POST', '/relation3s', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['relation2s' => ['/relation2s/1', '/relation2s/2']],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Each format produces a different ordering of tags but the set must match.
        $expected = ['/relation3s/1#relation2s', '/relation3s/1', '/relation3s'];
        sort($expected);

        foreach (['application/ld+json', 'application/hal+json', 'application/vnd.api+json'] as $accept) {
            $response = $client->request('GET', '/relation3s', ['headers' => ['Accept' => $accept]]);
            $this->assertResponseStatusCodeSame(200);
            $actual = explode(',', $response->getHeaders()['cache-tags'][0] ?? '');
            sort($actual);
            $this->assertSame($expected, $actual, \sprintf('Cache-Tags mismatch for %s', $accept));
        }
    }

    private function isMongoDB(): bool
    {
        return 'mongodb' === static::getContainer()->getParameter('kernel.environment');
    }
}
