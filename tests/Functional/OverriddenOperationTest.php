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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OverriddenOperationDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RPC;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class OverriddenOperationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [OverriddenOperationDummy::class, RPC::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([OverriddenOperationDummy::class]);
    }

    private function createDummy(): void
    {
        self::createClient()->request('POST', '/overridden_operation_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'name' => 'My Overridden Operation Dummy',
                'description' => 'Gerard',
                'alias' => 'notWritable',
            ],
        ]);
    }

    public function testCreateRespectsNotWritable(): void
    {
        $this->createDummy();

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/OverriddenOperationDummy',
            '@id' => '/overridden_operation_dummies/1',
            '@type' => 'OverriddenOperationDummy',
            'name' => 'My Overridden Operation Dummy',
            'alias' => null,
            'description' => 'Gerard',
        ]);
    }

    public function testGetItem(): void
    {
        $this->createDummy();

        self::createClient()->request('GET', '/overridden_operation_dummies/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');
        $this->assertJsonEquals([
            '@context' => '/contexts/OverriddenOperationDummy',
            '@id' => '/overridden_operation_dummies/1',
            '@type' => 'OverriddenOperationDummy',
            'name' => 'My Overridden Operation Dummy',
            'alias' => null,
            'description' => 'Gerard',
        ]);
    }

    public function testGetItemInXml(): void
    {
        $this->createDummy();

        $response = self::createClient()->request('GET', '/overridden_operation_dummies/1', [
            'headers' => ['Accept' => 'application/xml'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
        $this->assertSame(
            '<?xml version="1.0"?>'."\n".'<response><name>My Overridden Operation Dummy</name><alias/><description>Gerard</description></response>'."\n",
            $response->getContent()
        );
    }

    public function testNotFound(): void
    {
        self::createClient()->request('GET', '/overridden_operation_dummies/42');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetCollection(): void
    {
        $this->createDummy();

        self::createClient()->request('GET', '/overridden_operation_dummies');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/OverriddenOperationDummy',
            '@id' => '/overridden_operation_dummies',
            '@type' => 'hydra:Collection',
            'hydra:member' => [[
                '@id' => '/overridden_operation_dummies/1',
                '@type' => 'OverriddenOperationDummy',
                'name' => 'My Overridden Operation Dummy',
                'alias' => null,
                'description' => 'Gerard',
            ]],
            'hydra:totalItems' => 1,
        ]);
    }

    public function testPutHidesName(): void
    {
        $this->createDummy();

        self::createClient()->request('PUT', '/overridden_operation_dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                '@id' => '/overridden_operation_dummies/1',
                'name' => 'A nice dummy',
                'alias' => 'Dummy',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/OverriddenOperationDummy',
            '@id' => '/overridden_operation_dummies/1',
            '@type' => 'OverriddenOperationDummy',
            'alias' => 'Dummy',
            'description' => 'Gerard',
        ]);
    }

    public function testGetItemAfterPutShowsName(): void
    {
        $this->createDummy();
        self::createClient()->request('PUT', '/overridden_operation_dummies/1', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['@id' => '/overridden_operation_dummies/1', 'name' => 'A nice dummy', 'alias' => 'Dummy'],
        ]);

        self::createClient()->request('GET', '/overridden_operation_dummies/1');

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonEquals([
            '@context' => '/contexts/OverriddenOperationDummy',
            '@id' => '/overridden_operation_dummies/1',
            '@type' => 'OverriddenOperationDummy',
            'name' => 'My Overridden Operation Dummy',
            'alias' => 'Dummy',
            'description' => 'Gerard',
        ]);
    }

    public function testDelete(): void
    {
        $this->createDummy();

        self::createClient()->request('DELETE', '/overridden_operation_dummies/1');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testRpcMessengerOperationReturns202(): void
    {
        self::createClient()->request('POST', '/rpc', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['value' => 'Hello world'],
        ]);

        $this->assertResponseStatusCodeSame(202);
    }

    public function testRpcOperationWithOutputDtoReturns200(): void
    {
        self::createClient()->request('POST', '/rpc_output', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => ['value' => 'Hello world'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['success' => 'YES', '@type' => 'RPCOutput']);
    }
}
