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

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\SymfonyUlidDevice;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Uuid\SymfonyUlidDeviceEndpoint;

class UuidFilterWithSymfonyUlidTest extends UuidFilterBaseTestCase
{
    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            self::getDeviceClass(),
            self::getDeviceEndpointClass(),
        ];
    }

    /**
     * @return class-string
     */
    protected static function getDeviceEndpointClass(): string
    {
        return SymfonyUlidDeviceEndpoint::class;
    }

    /**
     * @return class-string
     */
    protected static function getDeviceClass(): string
    {
        return SymfonyUlidDevice::class;
    }

    public function getUrlPrefix(): string
    {
        return 'symfony_ulid';
    }

    public function geTypePrefix(): string
    {
        return 'SymfonyUlid';
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
                'description' => '',
                'required' => false,
                'deprecated' => false,
                'schema' => [
                    'type' => 'string',
                    'format' => 'ulid',
                ],
                'style' => 'form',
                'explode' => false,
            ],
            $json['paths']['/'.$this->getUrlPrefix().'_device_endpoints']['get']['parameters']
        );

        self::assertContains(
            [
                'name' => 'id[]',
                'in' => 'query',
                'description' => 'One or more Ulids',
                'required' => false,
                'deprecated' => false,
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'format' => 'ulid',
                    ],
                ],
                'style' => 'deepObject',
                'explode' => true,
            ],
            $json['paths']['/'.$this->getUrlPrefix().'_device_endpoints']['get']['parameters']
        );
    }
}
