<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\CacheWarmer;

use Dunglas\ApiBundle\CacheWarmer\MetadataCacheWarmer;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class MetadataCacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    public function testWarmUp()
    {
        $resourceProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $resourceProphecy->getEntityClass()->willReturn('Foo')->shouldBeCalled();
        $resourceProphecy->getNormalizationGroups()->willReturn(null)->shouldBeCalled();
        $resourceProphecy->getDenormalizationGroups()->willReturn(null)->shouldBeCalled();
        $resourceProphecy->getValidationGroups()->willReturn([])->shouldBeCalled();
        $resource = $resourceProphecy->reveal();

        // Mock the implementation to avoid getIterator() troubles
        $resourceCollectionProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceCollection');
        $resourceCollectionProphecy->getIterator()->willReturn(new \ArrayObject([$resource]))->shouldBeCalled();
        $resourceCollection = $resourceCollectionProphecy->reveal();

        $classMetadataFactoryProphecy = $this->prophesize('Dunglas\ApiBundle\Mapping\Factory\ClassMetadataFactoryInterface');
        $classMetadataFactoryProphecy->getMetadataFor('Foo', null, null, [])->shouldBeCalled();
        $classMetadataFactory = $classMetadataFactoryProphecy->reveal();

        $cacheWarmer = new MetadataCacheWarmer($resourceCollection, $classMetadataFactory);

        $this->assertInstanceOf('Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface', $cacheWarmer);
        $this->assertTrue($cacheWarmer->isOptional());

        $cacheWarmer->warmUp('foo');
    }
}
