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
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Tomasz Grochowski <tg@urias.it>
 */
class IdentifiersExtractorTest extends TestCase
{
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

        $operation = $this->prophesize(Operation::class);
        $item = new Dummy();
        $resourceClass = Dummy::class;
        $context = [
            'operation' => $operation->reveal()
        ];

        $resourceClassResolverProphecy->getResourceClass($item)->willReturn($resourceClass);
        $operation->getUriVariables()->willReturn([]);

        $this->assertEquals([], $identifiersExtractor->getIdentifiersFromItem($item, 'operation', $context));
    }

}
