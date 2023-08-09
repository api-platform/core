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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\MainControllerResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;

class MainControllerResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceMetadataCollection('Dummy', [
            new ApiResource(
                shortName: 'AttributeResource',
                class: 'Dummy',
                operations: [
                    'get' => new Get(shortName: 'AttributeResource', class: 'Dummy'),
                ]
            ),
        ]));

        $apiResource = (new MainControllerResourceMetadataCollectionFactory($decorated))->create('Dummy');
        $operation = $apiResource->getOperation();
        $this->assertInstanceOf(HttpOperation::class, $operation);
        $this->assertEquals($operation->getController(), 'api_platform.symfony.main_controller');
    }
}
