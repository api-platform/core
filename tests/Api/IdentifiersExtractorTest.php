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

namespace ApiPlatform\Tests\Api;

use ApiPlatform\Api\IdentifiersExtractor;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Tomasz Grochowski <tg@urias.it>
 */
class IdentifiersExtractorTest extends TestCase
{
    use ProphecyTrait;

    public function testGetIdentifiersFromItem()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $resourceClassResolver = $resourceClassResolverProphecy->reveal();

        $identifiersExtractor = new IdentifiersExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolver,
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal()
        );

        $operation = $this->prophesize(HttpOperation::class);
        $item = new Dummy();
        $resourceClass = Dummy::class;

        $resourceClassResolverProphecy->isResourceClass(Argument::any())->willReturn(true);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn($resourceClass);
        $operation->getUriVariables()->willReturn([]);

        $this->assertEquals([], $identifiersExtractor->getIdentifiersFromItem($item, $operation->reveal()));
    }

    public function testGetIdentifiersFromItemWithId()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $resourceClassResolverProphecy->isResourceClass(Argument::any())->willReturn(true);
        $resourceClassResolver = $resourceClassResolverProphecy->reveal();

        $identifiersExtractor = new IdentifiersExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolver,
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal()
        );

        $operation = (new Get())->withUriVariables(['id' => (new Link())->withIdentifiers(['id'])->withFromClass(Dummy::class)->withParameterName('id')]);
        $item = new Dummy();
        $item->setId(1);
        $resourceClass = Dummy::class;

        $resourceClassResolverProphecy->getResourceClass($item)->willReturn($resourceClass);

        $this->assertEquals(['id' => '1'], $identifiersExtractor->getIdentifiersFromItem($item, $operation));
    }
}
