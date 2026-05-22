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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ProcessorEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ProviderEntity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ProviderProcessorEntityTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ProcessorEntity::class, ProviderEntity::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([ProcessorEntity::class, ProviderEntity::class]);
    }

    public function testCreateProcessorEntity(): void
    {
        self::createClient()->request('POST', '/processor_entities', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['foo' => 'bar'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertResponseHeaderSame('Content-Location', '/processor_entities/1.jsonld');
        $this->assertResponseHeaderSame('Location', '/processor_entities/1');
        $this->assertJsonEquals([
            '@context' => '/contexts/ProcessorEntity',
            '@id' => '/processor_entities/1',
            '@type' => 'ProcessorEntity',
            'id' => 1,
            'foo' => 'bar',
        ]);
    }

    public function testCreateProviderEntity(): void
    {
        self::createClient()->request('POST', '/provider_entities', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['foo' => 'bar'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertResponseHeaderSame('Content-Location', '/provider_entities/1.jsonld');
        $this->assertResponseHeaderSame('Location', '/provider_entities/1');
        $this->assertJsonEquals([
            '@context' => '/contexts/ProviderEntity',
            '@id' => '/provider_entities/1',
            '@type' => 'ProviderEntity',
            'id' => 1,
            'foo' => 'bar',
        ]);
    }

    public function testGetProviderEntityCollection(): void
    {
        self::createClient()->request('POST', '/provider_entities', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['foo' => 'bar'],
        ]);

        $response = self::createClient()->request('GET', '/provider_entities');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertArrayNotHasKey('content-location', array_change_key_case($response->getHeaders()));
        $this->assertJsonEquals([
            '@context' => '/contexts/ProviderEntity',
            '@id' => '/provider_entities',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/provider_entities/1',
                '@type' => 'ProviderEntity',
                'id' => 1,
                'foo' => 'bar',
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testGetProviderEntityItem(): void
    {
        self::createClient()->request('POST', '/provider_entities', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['foo' => 'bar'],
        ]);

        $response = self::createClient()->request('GET', '/provider_entities/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertArrayNotHasKey('content-location', array_change_key_case($response->getHeaders()));
        $this->assertJsonEquals([
            '@context' => '/contexts/ProviderEntity',
            '@id' => '/provider_entities/1',
            '@type' => 'ProviderEntity',
            'id' => 1,
            'foo' => 'bar',
        ]);
    }
}
