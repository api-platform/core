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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\DefaultsResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

class DefaultsResourceMetadataFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadata(
            'foo',
            null,
            null,
            null,
            null,
            [
                'mercure' => true,
            ]
        ));

        $defaults = [
            'shortName' => 'bar',
            'description' => 'A Foo entity',
            'attributes' => [
                'mercure' => false,
                'messenger' => true,
            ],
        ];

        $factory = new DefaultsResourceMetadataFactory($decoratedProphecy->reveal(), $defaults);

        $resourceMetadata = $factory->create(Dummy::class);
        $this->assertEquals('foo', $resourceMetadata->getShortName());
        $this->assertEquals('A Foo entity', $resourceMetadata->getDescription());
        $this->assertTrue($resourceMetadata->getAttribute('mercure'));
        $this->assertTrue($resourceMetadata->getAttribute('messenger'));
    }
}
