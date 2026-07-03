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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AbstractDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConcreteDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CrudAbstractTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [AbstractDummy::class, ConcreteDummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([AbstractDummy::class, ConcreteDummy::class]);
    }

    private function createConcrete(): void
    {
        self::createClient()->request('POST', '/concrete_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['instance' => 'Concrete', 'name' => 'My Dummy'],
        ]);
    }

    public function testCreateConcrete(): void
    {
        $this->createConcrete();

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertResponseHeaderSame('Content-Location', '/concrete_dummies/1.jsonld');
        $this->assertResponseHeaderSame('Location', '/concrete_dummies/1');
        $this->assertJsonEquals([
            '@context' => '/contexts/ConcreteDummy',
            '@id' => '/concrete_dummies/1',
            '@type' => 'ConcreteDummy',
            'instance' => 'Concrete',
            'id' => 1,
            'name' => 'My Dummy',
        ]);
    }

    public function testGetItemViaAbstractUri(): void
    {
        $this->createConcrete();

        $response = self::createClient()->request('GET', '/abstract_dummies/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertArrayNotHasKey('content-location', array_change_key_case($response->getHeaders()));
        $this->assertJsonEquals([
            '@context' => '/contexts/ConcreteDummy',
            '@id' => '/concrete_dummies/1',
            '@type' => 'ConcreteDummy',
            'instance' => 'Concrete',
            'id' => 1,
            'name' => 'My Dummy',
        ]);
    }

    public function testGetCollectionViaAbstractUri(): void
    {
        $this->createConcrete();

        $response = self::createClient()->request('GET', '/abstract_dummies');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, \count($data['hydra:member']));
        $this->assertSame('ConcreteDummy', $data['hydra:member'][0]['@type']);
        $this->assertNotEmpty($data['hydra:member'][0]['instance']);
    }

    public function testUpdateConcreteUri(): void
    {
        $this->createConcrete();

        self::createClient()->request('PUT', '/concrete_dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['@id' => '/concrete_dummies/1', 'instance' => 'Become real', 'name' => 'A nice dummy'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Location', '/concrete_dummies/1.jsonld');
        $this->assertJsonEquals([
            '@context' => '/contexts/ConcreteDummy',
            '@id' => '/concrete_dummies/1',
            '@type' => 'ConcreteDummy',
            'instance' => 'Become real',
            'id' => 1,
            'name' => 'A nice dummy',
        ]);
    }

    public function testUpdateConcreteViaAbstractUri(): void
    {
        $this->createConcrete();

        self::createClient()->request('PUT', '/abstract_dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['@id' => '/concrete_dummies/1', 'instance' => 'Become surreal', 'name' => 'A nicer dummy'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Location', '/concrete_dummies/1.jsonld');
        $this->assertJsonEquals([
            '@context' => '/contexts/ConcreteDummy',
            '@id' => '/concrete_dummies/1',
            '@type' => 'ConcreteDummy',
            'instance' => 'Become surreal',
            'id' => 1,
            'name' => 'A nicer dummy',
        ]);
    }

    public function testDeleteViaAbstractUri(): void
    {
        $this->createConcrete();

        self::createClient()->request('DELETE', '/abstract_dummies/1');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testCreateConcreteViaDiscriminatorOnAbstract(): void
    {
        self::createClient()->request('POST', '/abstract_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['discr' => 'concrete', 'instance' => 'Concrete', 'name' => 'My Dummy'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Location', '/concrete_dummies/1.jsonld');
        $this->assertResponseHeaderSame('Location', '/concrete_dummies/1');
        $this->assertJsonEquals([
            '@context' => '/contexts/ConcreteDummy',
            '@id' => '/concrete_dummies/1',
            '@type' => 'ConcreteDummy',
            'instance' => 'Concrete',
            'id' => 1,
            'name' => 'My Dummy',
        ]);
    }
}
