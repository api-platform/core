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

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\CachedIdentifiersExtractor;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Generator\Uuid;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class CachedIdentifiersExtractorTest extends TestCase
{
    public function itemProvider()
    {
        $dummy = new Dummy();
        $dummy->setId($id = 1);
        yield [$dummy, ['id' => $id]];

        $dummy = new Dummy();
        $dummy->setId($id = new Uuid());
        yield [$dummy, ['id' => $id]];
    }

    /**
     * @dataProvider itemProvider
     */
    public function testFirstPass($item, $expected)
    {
        $key = 'iri_identifiers'.md5(Dummy::class);

        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->shouldBeCalled()->willReturn(false);
        $cacheItem->set(['id'])->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($key)->shouldBeCalled()->willReturn($cacheItem);
        $cacheItemPool->save($cacheItem)->shouldBeCalled();

        $decoration = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoration->getIdentifiersFromItem($item)->shouldBeCalled()->willReturn($expected);

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPool->reveal(), $decoration->reveal(), null, $this->getResourceClassResolver());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item), 'Trigger the local cache');

        $decoration->getIdentifiersFromResourceClass(Dummy::class)->shouldBeCalled()->willReturn(['id']);

        $expectedResult = ['id'];
        $this->assertSame($expectedResult, $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class));
        $this->assertSame($expectedResult, $identifiersExtractor->getIdentifiersFromResourceClass(Dummy::class), 'Trigger the local cache');
    }

    /**
     * @dataProvider itemProvider
     */
    public function testSecondPass($item, $expected)
    {
        $key = 'iri_identifiers'.md5(Dummy::class);

        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->shouldBeCalled()->willReturn(true);
        $cacheItem->get()->shouldBeCalled()->willReturn(['id']);

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($key)->shouldBeCalled()->willReturn($cacheItem);

        $decoration = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoration->getIdentifiersFromItem($item)->shouldNotBeCalled();

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPool->reveal(), $decoration->reveal(), null, $this->getResourceClassResolver());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item), 'Trigger the local cache');
    }

    public function identifiersRelatedProvider()
    {
        $related = new RelatedDummy();
        $related->setId($relatedId = 2);

        $dummy = new Dummy();
        $dummy->setId($id = 1);
        $dummy->setRelatedDummy($related);

        yield [$dummy, ['id' => $id, 'relatedDummy' => $relatedId]];

        $related = new RelatedDummy();
        $related->setId($relatedId = 1);

        $dummy = new Dummy();
        $dummy->setId($id = new Uuid());
        $dummy->setRelatedDummy($related);

        yield [$dummy, ['id' => $id, 'relatedDummy' => $relatedId]];

        $related = new RelatedDummy();
        $related->setId($relatedId = new Uuid());

        $dummy = new Dummy();
        $dummy->setId($id = new Uuid());
        $dummy->setRelatedDummy($related);

        yield [$dummy, ['id' => $id, 'relatedDummy' => $relatedId]];
    }

    /**
     * @dataProvider identifiersRelatedProvider
     */
    public function testFirstPassWithRelated($item, $expected)
    {
        $key = 'iri_identifiers'.md5(Dummy::class);
        $keyRelated = 'iri_identifiers'.md5(RelatedDummy::class);

        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->shouldBeCalled()->willReturn(true);
        $cacheItem->get()->shouldBeCalled()->willReturn(['id', 'relatedDummy']);

        $cacheItemRelated = $this->prophesize(CacheItemInterface::class);
        $cacheItemRelated->isHit()->shouldBeCalled()->willReturn(false);

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($key)->shouldBeCalled()->willReturn($cacheItem);
        $cacheItemPool->getItem($keyRelated)->shouldBeCalled()->willReturn($cacheItemRelated);

        $decoration = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoration->getIdentifiersFromItem($item)->shouldBeCalled()->willReturn($expected);

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPool->reveal(), $decoration->reveal(), null, $this->getResourceClassResolver());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item), 'Trigger the local cache');
    }

    /**
     * @dataProvider identifiersRelatedProvider
     */
    public function testSecondPassWithRelated($item, $expected)
    {
        $key = 'iri_identifiers'.md5(Dummy::class);
        $keyRelated = 'iri_identifiers'.md5(RelatedDummy::class);

        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->shouldBeCalled()->willReturn(true);
        $cacheItem->get()->shouldBeCalled()->willReturn(['id', 'relatedDummy']);

        $cacheItemRelated = $this->prophesize(CacheItemInterface::class);
        $cacheItemRelated->isHit()->shouldBeCalled()->willReturn(true);
        $cacheItemRelated->get()->shouldBeCalled()->willReturn(['id']);

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($key)->shouldBeCalled()->willReturn($cacheItem);
        $cacheItemPool->getItem($keyRelated)->shouldBeCalled()->willReturn($cacheItemRelated);

        $decoration = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoration->getIdentifiersFromItem($item)->shouldNotBeCalled();

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPool->reveal(), $decoration->reveal(), null, $this->getResourceClassResolver());

        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item));
        $this->assertSame($expected, $identifiersExtractor->getIdentifiersFromItem($item), 'Trigger the local cache');
    }

    /**
     * @group legacy
     * @expectedDeprecation Not injecting ApiPlatform\Core\Api\ResourceClassResolverInterface in the CachedIdentifiersExtractor might introduce cache issues with object identifiers.
     */
    public function testDeprecationResourceClassResolver()
    {
        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $decoration = $this->prophesize(IdentifiersExtractorInterface::class);

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPool->reveal(), $decoration->reveal(), null);
    }

    private function getResourceClassResolver()
    {
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Argument::type('string'))->will(function ($args) {
            if (Uuid::class === $args[0]) {
                return false;
            }

            return true;
        });

        return $resourceClassResolver->reveal();
    }
}
