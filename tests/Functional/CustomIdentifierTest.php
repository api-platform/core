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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomIdentifierDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomMultipleIdentifierDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CustomIdentifierTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CustomIdentifierDummy::class, CustomMultipleIdentifierDummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([CustomIdentifierDummy::class, CustomMultipleIdentifierDummy::class]);
    }

    private function createDummy(): void
    {
        self::createClient()->request('POST', '/custom_identifier_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'My Dummy'],
        ]);
    }

    public function testCreate(): void
    {
        $this->createDummy();

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomIdentifierDummy',
            '@id' => '/custom_identifier_dummies/1',
            '@type' => 'CustomIdentifierDummy',
            'customId' => 1,
            'name' => 'My Dummy',
        ]);
    }

    public function testGetItem(): void
    {
        $this->createDummy();

        self::createClient()->request('GET', '/custom_identifier_dummies/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomIdentifierDummy',
            '@id' => '/custom_identifier_dummies/1',
            '@type' => 'CustomIdentifierDummy',
            'customId' => 1,
            'name' => 'My Dummy',
        ]);
    }

    public function testGetCollection(): void
    {
        $this->createDummy();

        self::createClient()->request('GET', '/custom_identifier_dummies');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomIdentifierDummy',
            '@id' => '/custom_identifier_dummies',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/custom_identifier_dummies/1',
                '@type' => 'CustomIdentifierDummy',
                'customId' => 1,
                'name' => 'My Dummy',
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testUpdate(): void
    {
        $this->createDummy();

        self::createClient()->request('PUT', '/custom_identifier_dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'My Dummy modified'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomIdentifierDummy',
            '@id' => '/custom_identifier_dummies/1',
            '@type' => 'CustomIdentifierDummy',
            'customId' => 1,
            'name' => 'My Dummy modified',
        ]);
    }

    public function testApiDocReportsCustomIdentifierClass(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonld');

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $classes = array_filter($data['hydra:supportedClass'], static fn ($c) => 'CustomIdentifierDummy' === $c['hydra:title']);
        $this->assertCount(1, $classes, 'CustomIdentifierDummy is missing from /docs.jsonld');
        $class = reset($classes);
        $properties = array_column($class['hydra:supportedProperty'] ?? [], 'hydra:title');
        $this->assertContains('name', $properties);
    }

    public function testDelete(): void
    {
        $this->createDummy();

        self::createClient()->request('DELETE', '/custom_identifier_dummies/1');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testGetCustomMultipleIdentifierDummy(): void
    {
        $manager = $this->getManager();
        $dummy = new CustomMultipleIdentifierDummy();
        $dummy->setName('Orwell');
        $dummy->setFirstId(1);
        $dummy->setSecondId(2);
        $manager->persist($dummy);
        $manager->flush();

        self::createClient()->request('GET', '/custom_multiple_identifier_dummies/1/2');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomMultipleIdentifierDummy',
            '@id' => '/custom_multiple_identifier_dummies/1/2',
            '@type' => 'CustomMultipleIdentifierDummy',
            'firstId' => 1,
            'secondId' => 2,
            'name' => 'Orwell',
        ]);
    }
}
