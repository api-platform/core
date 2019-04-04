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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Messenger;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Messenger\DataTransformer;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class DataTransformerTest extends TestCase
{
    public function testSupport()
    {
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['messenger' => 'input']));

        $dataTransformer = new DataTransformer($metadataFactoryProphecy->reveal());
        $this->assertTrue($dataTransformer->supportsTransformation([], Dummy::class, ['input' => ['class' => 'smth']]));
    }

    public function testSupportWithinRequest()
    {
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, ['foo' => ['messenger' => 'input']], null, []));

        $dataTransformer = new DataTransformer($metadataFactoryProphecy->reveal());
        $this->assertTrue($dataTransformer->supportsTransformation([], Dummy::class, ['input' => ['class' => 'smth'], 'operation_type' => OperationType::ITEM, 'item_operation_name' => 'foo']));
    }

    public function testNoSupport()
    {
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['messenger' => true]));

        $dataTransformer = new DataTransformer($metadataFactoryProphecy->reveal());
        $this->assertFalse($dataTransformer->supportsTransformation([], Dummy::class, ['input' => ['class' => 'smth']]));
    }

    public function testNoSupportWithinRequest()
    {
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, ['foo' => ['messenger' => true]], null, []));

        $dataTransformer = new DataTransformer($metadataFactoryProphecy->reveal());
        $this->assertFalse($dataTransformer->supportsTransformation([], Dummy::class, ['input' => ['class' => 'smth'], 'operation_type' => OperationType::ITEM, 'item_operation_name' => 'foo']));
    }

    public function testNoSupportWithoutInput()
    {
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['messenger' => 'input']));

        $dataTransformer = new DataTransformer($metadataFactoryProphecy->reveal());
        $this->assertFalse($dataTransformer->supportsTransformation([], Dummy::class, []));
    }

    public function testNoSupportWithObject()
    {
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['messenger' => 'input']));

        $dataTransformer = new DataTransformer($metadataFactoryProphecy->reveal());
        $this->assertFalse($dataTransformer->supportsTransformation(new Dummy(), Dummy::class, []));
    }

    public function testTransform()
    {
        $dummy = new Dummy();
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $dataTransformer = new DataTransformer($metadataFactoryProphecy->reveal());
        $this->assertSame($dummy, $dataTransformer->transform($dummy, Dummy::class));
    }

    public function testSupportWithGraphqlContext()
    {
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata(null, null, null, null, null, []))->withGraphQl(['create' => ['messenger' => 'input']]));
        $dataTransformer = new DataTransformer($metadataFactoryProphecy->reveal());
        $this->assertTrue($dataTransformer->supportsTransformation([], Dummy::class, ['input' => ['class' => 'smth'], 'graphql_operation_name' => 'create']));
    }
}
