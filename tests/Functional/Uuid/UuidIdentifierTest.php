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

namespace ApiPlatform\Tests\Functional\Uuid;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CustomGeneratedIdentifier;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RamseyUuidDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SymfonyUuidDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\UuidIdentifierDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class UuidIdentifierTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [UuidIdentifierDummy::class, RamseyUuidDummy::class, CustomGeneratedIdentifier::class, SymfonyUuidDummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([UuidIdentifierDummy::class, CustomGeneratedIdentifier::class]);
    }

    private function createUuidDummy(): void
    {
        self::createClient()->request('POST', '/uuid_identifier_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'My Dummy', 'uuid' => '41b29566-144b-11e6-a148-3e1d05defe78'],
        ]);
    }

    public function testCreateUuidIdentifier(): void
    {
        $this->createUuidDummy();

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertResponseHeaderSame('Content-Location', '/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78.jsonld');
        $this->assertResponseHeaderSame('Location', '/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78');
    }

    public function testGetUuidItem(): void
    {
        $this->createUuidDummy();

        self::createClient()->request('GET', '/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/UuidIdentifierDummy',
            '@id' => '/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78',
            '@type' => 'UuidIdentifierDummy',
            'uuid' => '41b29566-144b-11e6-a148-3e1d05defe78',
            'name' => 'My Dummy',
        ]);
    }

    public function testGetUuidCollection(): void
    {
        $this->createUuidDummy();

        self::createClient()->request('GET', '/uuid_identifier_dummies');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/UuidIdentifierDummy',
            '@id' => '/uuid_identifier_dummies',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78',
                '@type' => 'UuidIdentifierDummy',
                'uuid' => '41b29566-144b-11e6-a148-3e1d05defe78',
                'name' => 'My Dummy',
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testPutUuidIdentifier(): void
    {
        $this->createUuidDummy();

        self::createClient()->request('PUT', '/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => 'My Dummy modified'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Location', '/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78.jsonld');
        $this->assertJsonEquals([
            '@context' => '/contexts/UuidIdentifierDummy',
            '@id' => '/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78',
            '@type' => 'UuidIdentifierDummy',
            'uuid' => '41b29566-144b-11e6-a148-3e1d05defe78',
            'name' => 'My Dummy modified',
        ]);
    }

    public function testCustomGeneratedIdentifier(): void
    {
        self::createClient()->request('POST', '/custom_generated_identifiers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => new \stdClass(),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Location', '/custom_generated_identifiers/foo.jsonld');
        $this->assertResponseHeaderSame('Location', '/custom_generated_identifiers/foo');
        $this->assertJsonEquals([
            '@context' => '/contexts/CustomGeneratedIdentifier',
            '@id' => '/custom_generated_identifiers/foo',
            '@type' => 'CustomGeneratedIdentifier',
            'id' => 'foo',
        ]);
    }

    public function testDeleteUuid(): void
    {
        $this->createUuidDummy();

        self::createClient()->request('DELETE', '/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testGetRamseyUuidDummy(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
        $this->recreateSchema([RamseyUuidDummy::class]);

        $manager = $this->getManager();
        $dummy = new RamseyUuidDummy(RamseyUuid::fromString('41B29566-144B-11E6-A148-3E1D05DEFE78'));
        $manager->persist($dummy);
        $manager->flush();

        self::createClient()->request('GET', '/ramsey_uuid_dummies/41B29566-144B-11E6-A148-3E1D05DEFE78');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
    }

    public function testDeleteRamseyUuidDummy(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
        $this->recreateSchema([RamseyUuidDummy::class]);

        $manager = $this->getManager();
        $dummy = new RamseyUuidDummy(RamseyUuid::fromString('41B29566-144B-11E6-A148-3E1D05DEFE78'));
        $manager->persist($dummy);
        $manager->flush();

        self::createClient()->request('DELETE', '/ramsey_uuid_dummies/41B29566-144B-11E6-A148-3E1D05DEFE78');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testRetrieveBadRamseyUuidReturns404(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        self::createClient()->request('GET', '/ramsey_uuid_dummies/41B29566-144B-E1D05DEFE78');

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testCreateRamseyUuidDummy(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
        $this->recreateSchema([RamseyUuidDummy::class]);

        self::createClient()->request('POST', '/ramsey_uuid_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['id' => '41b29566-144b-11e6-a148-3e1d05defe78'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
    }

    public function testCreateRamseyUuidDummyWithNonIdField(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
        $this->recreateSchema([RamseyUuidDummy::class]);

        self::createClient()->request('POST', '/ramsey_uuid_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['other' => '51b29566-144b-11e6-a148-3e1d05defe78'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
    }

    public function testUpdateRamseyUuidNonIdField(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
        $this->recreateSchema([RamseyUuidDummy::class]);

        $manager = $this->getManager();
        $dummy = new RamseyUuidDummy(RamseyUuid::fromString('41b29566-144b-11e6-a148-3e1d05defe78'));
        $manager->persist($dummy);
        $manager->flush();

        self::createClient()->request('PUT', '/ramsey_uuid_dummies/41b29566-144b-11e6-a148-3e1d05defe78', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['other' => '61b29566-144b-11e6-a148-3e1d05defe78'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
    }

    public function testCreateBadRamseyUuidReturns400(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
        $this->recreateSchema([RamseyUuidDummy::class]);

        self::createClient()->request('POST', '/ramsey_uuid_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['id' => '41b29566-144b-e1d05defe78'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testUpdateBadRamseyUuidReturns400(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
        $this->recreateSchema([RamseyUuidDummy::class]);

        $manager = $this->getManager();
        $dummy = new RamseyUuidDummy(RamseyUuid::fromString('41b29566-144b-11e6-a148-3e1d05defe78'));
        $manager->persist($dummy);
        $manager->flush();

        self::createClient()->request('PUT', '/ramsey_uuid_dummies/41b29566-144b-11e6-a148-3e1d05defe78', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['other' => '61b29566-144b-e1d05defe78'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('Content-Type', 'application/problem+json; charset=utf-8');
    }

    public function testGetSymfonyUuidDummy(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
        $this->recreateSchema([SymfonyUuidDummy::class]);

        $manager = $this->getManager();
        $dummy = new SymfonyUuidDummy(SymfonyUuid::fromString('cdf8f706-ebe3-4fb6-b0bd-ae7b48028f24'));
        $manager->persist($dummy);
        $manager->flush();

        self::createClient()->request('GET', '/symfony_uuid_dummies/cdf8f706-ebe3-4fb6-b0bd-ae7b48028f24');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
    }
}
