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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomNormalizedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedNormalizedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CustomNormalizedTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CustomNormalizedDummy::class, RelatedNormalizedDummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([CustomNormalizedDummy::class, RelatedNormalizedDummy::class]);
    }

    private function createCustom(): void
    {
        self::createClient()->request('POST', '/custom_normalized_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'My Dummy', 'alias' => 'My alias'],
        ]);
    }

    public function testCreateCustomNormalized(): void
    {
        $this->createCustom();

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertResponseHeaderSame('Content-Location', '/custom_normalized_dummies/1.jsonld');
        $this->assertResponseHeaderSame('Location', '/custom_normalized_dummies/1');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomNormalizedDummy',
            '@id' => '/custom_normalized_dummies/1',
            '@type' => 'CustomNormalizedDummy',
            'id' => 1,
            'name' => 'My Dummy',
            'alias' => 'My alias',
        ]);
    }

    public function testCreateRelatedNormalizedReturnsJson(): void
    {
        self::createClient()->request('POST', '/related_normalized_dummies', [
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'json' => ['name' => 'My Dummy'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');
        $this->assertResponseHeaderSame('Content-Location', '/related_normalized_dummies/1.json');
        $this->assertResponseHeaderSame('Location', '/related_normalized_dummies/1');
        $this->assertJsonEquals(['id' => 1, 'name' => 'My Dummy', 'customNormalizedDummy' => []]);
    }

    public function testPutRelatedNormalizedReplacesEmbeddedDummies(): void
    {
        $this->createCustom();
        self::createClient()->request('POST', '/related_normalized_dummies', [
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'json' => ['name' => 'My Dummy'],
        ]);

        self::createClient()->request('PUT', '/related_normalized_dummies/1', [
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'json' => [
                'name' => 'My Dummy',
                'customNormalizedDummy' => [[
                    '@context' => '/contexts/CustomNormalizedDummy',
                    '@id' => '/custom_normalized_dummies/1',
                    '@type' => 'CustomNormalizedDummy',
                    'id' => 1,
                    'name' => 'My Dummy',
                ]],
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/json; charset=utf-8');
        $this->assertResponseHeaderSame('Content-Location', '/related_normalized_dummies/1.json');
        $this->assertJsonEquals([
            'id' => 1,
            'name' => 'My Dummy',
            'customNormalizedDummy' => [['id' => 1, 'name' => 'My Dummy', 'alias' => 'My alias']],
        ]);
    }

    public function testGetCustomNormalizedItem(): void
    {
        $this->createCustom();

        self::createClient()->request('GET', '/custom_normalized_dummies/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomNormalizedDummy',
            '@id' => '/custom_normalized_dummies/1',
            '@type' => 'CustomNormalizedDummy',
            'id' => 1,
            'name' => 'My Dummy',
            'alias' => 'My alias',
        ]);
    }

    public function testGetCustomNormalizedCollection(): void
    {
        $this->createCustom();

        self::createClient()->request('GET', '/custom_normalized_dummies');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomNormalizedDummy',
            '@id' => '/custom_normalized_dummies',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/custom_normalized_dummies/1',
                '@type' => 'CustomNormalizedDummy',
                'id' => 1,
                'name' => 'My Dummy',
                'alias' => 'My alias',
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testPutCustomNormalizedRetainsExistingAlias(): void
    {
        $this->createCustom();

        self::createClient()->request('PUT', '/custom_normalized_dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'My Dummy modified'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Location', '/custom_normalized_dummies/1.jsonld');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomNormalizedDummy',
            '@id' => '/custom_normalized_dummies/1',
            '@type' => 'CustomNormalizedDummy',
            'id' => 1,
            'name' => 'My Dummy modified',
            'alias' => 'My alias',
        ]);
    }

    public function testPatchCustomNormalized(): void
    {
        $this->createCustom();

        self::createClient()->request('PATCH', '/custom_normalized_dummies/1', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => ['name' => 'My Dummy modified'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Location', '/custom_normalized_dummies/1.jsonld');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomNormalizedDummy',
            '@id' => '/custom_normalized_dummies/1',
            '@type' => 'CustomNormalizedDummy',
            'id' => 1,
            'name' => 'My Dummy modified',
            'alias' => 'My alias',
        ]);
    }

    public function testDeleteCustomNormalized(): void
    {
        $this->createCustom();

        self::createClient()->request('DELETE', '/custom_normalized_dummies/1');

        $this->assertResponseStatusCodeSame(204);
    }
}
