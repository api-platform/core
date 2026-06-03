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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CrudTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    private const DUMMY = [
        '@context' => '/contexts/Dummy',
        '@id' => '/dummies/1',
        '@type' => 'Dummy',
        'description' => null,
        'dummy' => null,
        'dummyBoolean' => null,
        'dummyDate' => '2015-03-01T10:00:00+00:00',
        'dummyFloat' => null,
        'dummyPrice' => null,
        'relatedDummy' => null,
        'relatedDummies' => [],
        'jsonData' => ['key' => ['value1', 'value2']],
        'arrayData' => [],
        'name_converted' => null,
        'relatedOwnedDummy' => null,
        'relatedOwningDummy' => null,
        'id' => 1,
        'name' => 'My Dummy',
        'alias' => null,
        'foo' => null,
    ];

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Dummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([Dummy::class]);
    }

    private function createDummy(): void
    {
        self::createClient()->request('POST', '/dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Dummy',
                'dummyDate' => '2015-03-01T10:00:00+00:00',
                'jsonData' => ['key' => ['value1', 'value2']],
            ],
        ]);
    }

    public function testCreateDummy(): void
    {
        $this->createDummy();

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertResponseHeaderSame('Content-Location', '/dummies/1.jsonld');
        $this->assertResponseHeaderSame('Location', '/dummies/1');
        $this->assertJsonContains(self::DUMMY);
    }

    public function testGetItem(): void
    {
        $this->createDummy();

        $response = self::createClient()->request('GET', '/dummies/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertArrayNotHasKey('content-location', array_change_key_case($response->getHeaders()));
        $this->assertJsonContains(self::DUMMY);
    }

    public function testCreateEmptyBodyReturns400(): void
    {
        self::createClient()->request('POST', '/dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains(['detail' => 'Syntax error']);
    }

    public function testNotFoundReturns404(): void
    {
        $response = self::createClient()->request('GET', '/dummies/42');

        $this->assertResponseStatusCodeSame(404);
        $this->assertArrayNotHasKey('content-location', array_change_key_case($response->getHeaders(false)));
    }

    public function testGetCollection(): void
    {
        $this->createDummy();

        $response = self::createClient()->request('GET', '/dummies');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $data = $response->toArray();
        $this->assertSame('/contexts/Dummy', $data['@context']);
        $this->assertSame('/dummies', $data['@id']);
        $this->assertSame('hydra:Collection', $data['@type']);
        $this->assertSame(1, $data['hydra:totalItems']);
        $this->assertSame('/dummies/1', $data['hydra:member'][0]['@id']);
        $this->assertSame('My Dummy', $data['hydra:member'][0]['name']);
    }

    public function testUpdateDummy(): void
    {
        $this->createDummy();

        self::createClient()->request('PUT', '/dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                '@id' => '/dummies/1',
                'name' => 'A nice dummy',
                'dummyDate' => '2018-12-01 13:12',
                'jsonData' => [['key' => 'value1'], ['key' => 'value2']],
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Location', '/dummies/1.jsonld');
        $this->assertJsonContains([
            '@id' => '/dummies/1',
            '@type' => 'Dummy',
            'name' => 'A nice dummy',
            'dummyDate' => '2018-12-01T13:12:00+00:00',
            'jsonData' => [['key' => 'value1'], ['key' => 'value2']],
            'id' => 1,
        ]);
    }

    public function testUpdateEmptyBodyReturns400(): void
    {
        $this->createDummy();

        self::createClient()->request('PUT', '/dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains(['detail' => 'Syntax error']);
    }

    public function testDeleteDummy(): void
    {
        $this->createDummy();

        self::createClient()->request('DELETE', '/dummies/1');

        $this->assertResponseStatusCodeSame(204);
    }
}
