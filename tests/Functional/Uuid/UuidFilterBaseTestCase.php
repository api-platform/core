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
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\HttpFoundation\Response;

abstract class UuidFilterBaseTestCase extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }
    }

    /**
     * @return class-string
     */
    abstract protected static function getDeviceEndpointClass(): string;

    /**
     * @return class-string
     */
    abstract protected static function getDeviceClass(): string;

    abstract public function getUrlPrefix(): string;

    abstract public function geTypePrefix(): string;

    public function createDeviceEndpoint(mixed ...$args): object
    {
        return (new \ReflectionClass(static::getDeviceEndpointClass()))->newInstanceArgs($args);
    }

    public function createDevice(mixed ...$args): object
    {
        return (new \ReflectionClass(static::getDeviceClass()))->newInstanceArgs($args);
    }

    public function testSearchFilterByUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($device = $this->createDevice());
        $manager->persist($this->createDevice());
        $manager->flush();

        $response = self::createClient()->request('GET', '/'.$this->getUrlPrefix().'_devices', [
            'query' => [
                'id' => (string) $device->id,
            ],
        ]);

        self::assertResponseIsSuccessful();
        $json = $response->toArray();

        self::assertArraySubset(['hydra:totalItems' => 1], $json);
        self::assertArraySubset(
            [
                'hydra:member' => [
                    [
                        '@id' => '/'.$this->getUrlPrefix().'_devices/'.$device->id,
                        '@type' => $this->geTypePrefix().'Device',
                        'id' => (string) $device->id,
                    ],
                ],
            ],
            $json
        );
    }

    public function testSearchFilterByManyUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($device = $this->createDevice());
        $manager->persist($otherDevice = $this->createDevice());
        $manager->persist($this->createDevice());
        $manager->flush();

        $response = self::createClient()->request('GET', '/'.$this->getUrlPrefix().'_devices', [
            'query' => [
                'id' => [
                    (string) $device->id,
                    (string) $otherDevice->id,
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $json = $response->toArray();

        self::assertArraySubset(['hydra:totalItems' => 2], $json);
        self::assertArraySubset(
            [
                'hydra:member' => [
                    [
                        '@id' => '/'.$this->getUrlPrefix().'_devices/'.$device->id,
                        '@type' => $this->geTypePrefix().'Device',
                        'id' => (string) $device->id,
                    ],
                    [
                        '@id' => '/'.$this->getUrlPrefix().'_devices/'.$otherDevice->id,
                        '@type' => $this->geTypePrefix().'Device',
                        'id' => (string) $otherDevice->id,
                    ],
                ],
            ],
            $json
        );
    }

    public function testSearchFilterByInvalidUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($this->createDevice());
        $manager->persist($this->createDevice());
        $manager->flush();

        self::createClient()->request('GET', '/'.$this->getUrlPrefix().'_devices', [
            'query' => [
                'id' => 'invalid-uuid',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testSearchFilterByManyInvalidUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($this->createDevice());
        $manager->persist($this->createDevice());
        $manager->flush();

        self::createClient()->request('GET', '/'.$this->getUrlPrefix().'_devices', [
            'query' => [
                'id' => ['invalid-uuid', 'other-invalid-uuid'],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testSearchFilterOnManyToOneRelationByUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($fooDevice = $this->createDevice());
        $manager->persist($barDevice = $this->createDevice());
        $manager->persist($this->createDeviceEndpoint(null, $fooDevice));
        $manager->persist($barDeviceEndpoint = $this->createDeviceEndpoint(null, $barDevice));
        $manager->flush();

        $response = self::createClient()->request('GET', '/'.$this->getUrlPrefix().'_device_endpoints', [
            'query' => [
                'myDevice' => (string) $barDevice->id,
            ],
        ]);

        self::assertResponseIsSuccessful();
        $json = $response->toArray();

        self::assertArraySubset(['hydra:totalItems' => 1], $json);
        self::assertArraySubset(
            [
                'hydra:member' => [
                    [
                        '@id' => '/'.$this->getUrlPrefix().'_device_endpoints/'.$barDeviceEndpoint->id,
                        '@type' => $this->geTypePrefix().'DeviceEndpoint',
                        'id' => (string) $barDeviceEndpoint->id,
                    ],
                ],
            ],
            $json
        );
    }

    public function testSearchFilterOnManyToOneRelationByManyUuid(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($fooDevice = $this->createDevice());
        $manager->persist($barDevice = $this->createDevice());
        $manager->persist($bazDevice = $this->createDevice());
        $manager->persist($fooDeviceEndpoint = $this->createDeviceEndpoint(null, $fooDevice));
        $manager->persist($barDeviceEndpoint = $this->createDeviceEndpoint(null, $barDevice));
        $manager->persist($this->createDeviceEndpoint(null, $bazDevice));
        $manager->flush();

        $response = self::createClient()->request('GET', '/'.$this->getUrlPrefix().'_device_endpoints', [
            'query' => [
                'myDevice' => [
                    (string) $fooDevice->id,
                    (string) $barDevice->id,
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();
        $json = $response->toArray();

        self::assertArraySubset(['hydra:totalItems' => 2], $json);
        self::assertArraySubset(
            [
                'hydra:member' => [
                    [
                        '@id' => '/'.$this->getUrlPrefix().'_device_endpoints/'.$fooDeviceEndpoint->id,
                        '@type' => $this->geTypePrefix().'DeviceEndpoint',
                        'id' => (string) $fooDeviceEndpoint->id,
                    ],
                    [
                        '@id' => '/'.$this->getUrlPrefix().'_device_endpoints/'.$barDeviceEndpoint->id,
                        '@type' => $this->geTypePrefix().'DeviceEndpoint',
                        'id' => (string) $barDeviceEndpoint->id,
                    ],
                ],
            ],
            $json
        );
    }

    public function testSearchFilterOnManyToOneRelationByInvalidUuids(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($fooDevice = $this->createDevice());
        $manager->persist($barDevice = $this->createDevice());
        $manager->persist($bazDevice = $this->createDevice());
        $manager->persist($this->createDeviceEndpoint(null, $fooDevice));
        $manager->persist($this->createDeviceEndpoint(null, $barDevice));
        $manager->persist($this->createDeviceEndpoint(null, $bazDevice));
        $manager->flush();

        self::createClient()->request('GET', '/'.$this->getUrlPrefix().'_device_endpoints', [
            'query' => [
                'myDevice' => [
                    'invalid-uuid',
                    'other-invalid-uuid',
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetOpenApiDescription(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $json = $response->toArray();

        self::assertContains(
            [
                'name' => 'id',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'string',
                    'format' => 'uuid',
                ],
            ],
            array_map(fn ($p) => array_intersect_key($p, ['name' => 1, 'in' => 1, 'required' => 1, 'schema' => 1]), $json['paths']['/'.$this->getUrlPrefix().'_device_endpoints']['get']['parameters'])
        );

        self::assertContains(
            [
                'name' => 'id[]',
                'in' => 'query',
                'required' => false,
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'format' => 'uuid',
                    ],
                ],
                'style' => 'deepObject',
                'explode' => true,
            ],
            array_map(fn ($p) => array_intersect_key($p, ['name' => 1, 'in' => 1, 'required' => 1, 'schema' => 1, 'style' => 1, 'explode' => 1]), $json['paths']['/'.$this->getUrlPrefix().'_device_endpoints']['get']['parameters'])
        );
    }

    public function testSearchFilterByUuidNested(): void
    {
        $this->recreateSchema(static::getResources());

        $manager = $this->getManager();
        $manager->persist($fooDevice = $this->createDevice());
        $manager->persist($barDevice = $this->createDevice());
        $manager->persist($this->createDeviceEndpoint(null, $fooDevice));
        $manager->persist($expectedDeviceEndpoint = $this->createDeviceEndpoint(null, $barDevice));
        $manager->flush();

        $response = self::createClient()->request('GET', '/'.$this->getUrlPrefix().'_device_endpoints', [
            'query' => [
                'myDeviceExternalIdAlias' => (string) $expectedDeviceEndpoint->myDevice->externalId,
            ],
        ]);

        self::assertResponseIsSuccessful();
        $json = $response->toArray();

        self::assertArraySubset(['hydra:totalItems' => 1], $json);
        self::assertArraySubset(
            [
                'hydra:member' => [
                    [
                        '@id' => '/'.$this->getUrlPrefix().'_device_endpoints/'.$expectedDeviceEndpoint->id,
                        '@type' => $this->geTypePrefix().'DeviceEndpoint',
                        'id' => (string) $expectedDeviceEndpoint->id,
                    ],
                ],
            ],
            $json
        );
    }

    protected function tearDown(): void
    {
        if ($this->isMongoDB()) {
            return;
        }

        $this->recreateSchema(static::getResources());

        parent::tearDown();
    }
}
