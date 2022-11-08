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

namespace ApiPlatform\Tests\GraphQl\Metadata\Factory;

use ApiPlatform\GraphQl\Metadata\Factory\GraphQlNestedOperationResourceMetadataFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

final class GraphQlNestedOperationResourceMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate(): void
    {
        $decorated = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->create('someClass')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('someClass'));

        $metadataFactory = new GraphQlNestedOperationResourceMetadataFactory(['status' => 500], $decorated->reveal());
        $apiResource = $metadataFactory->create('someClass')[0];
        $this->assertCount(5, $apiResource->getGraphQlOperations());
    }

    public function testCreateWithResource(): void
    {
        $metadataFactory = new GraphQlNestedOperationResourceMetadataFactory(['status' => 500]);
        $apiResource = $metadataFactory->create(RelatedDummy::class)[0];
        $this->assertEquals('RelatedDummy', $apiResource->getShortName());
    }
}
