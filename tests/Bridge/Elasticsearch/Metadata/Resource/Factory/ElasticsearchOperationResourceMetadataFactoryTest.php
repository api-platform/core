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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\Metadata\Resource\Factory;

use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Resource\Factory\ElasticsearchOperationResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use PHPUnit\Framework\TestCase;

class ElasticsearchOperationResourceMetadataFactoryTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            ResourceMetadataFactoryInterface::class,
            new ElasticsearchOperationResourceMetadataFactory($this->prophesize(ResourceMetadataFactoryInterface::class)->reveal())
        );
    }

    public function testCreate()
    {
        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $resourceMetadata = (new ElasticsearchOperationResourceMetadataFactory($decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame(['get' => ['method' => 'GET']], $resourceMetadata->getCollectionOperations());
        self::assertSame(['get' => ['method' => 'GET']], $resourceMetadata->getItemOperations());
    }

    public function testCreateWithExistingOperations()
    {
        $originalResourceMetadata = new ResourceMetadata();
        $originalResourceMetadata = $originalResourceMetadata->withItemOperations(['foo' => ['method' => 'GET']]);
        $originalResourceMetadata = $originalResourceMetadata->withCollectionOperations(['bar' => ['method' => 'GET']]);

        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create(Foo::class)->willReturn($originalResourceMetadata)->shouldBeCalled();

        $resourceMetadata = (new ElasticsearchOperationResourceMetadataFactory($decoratedProphecy->reveal()))->create(Foo::class);

        self::assertSame($originalResourceMetadata, $resourceMetadata);
        self::assertSame(['foo' => ['method' => 'GET']], $resourceMetadata->getItemOperations());
        self::assertSame(['bar' => ['method' => 'GET']], $resourceMetadata->getCollectionOperations());
    }
}
