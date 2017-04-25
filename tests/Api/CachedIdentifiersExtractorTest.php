<?php

declare(strict_types=1);

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\CachedIdentifiersExtractor;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class CachedIdentifiersExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function testFirstPass()
    {
        $key = 'iri_identifiers'.md5(Dummy::class);

        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->shouldBeCalled()->willReturn(false);
        $cacheItem->set(['id'])->shouldBeCalled();

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($key)->shouldBeCalled()->willReturn($cacheItem);
        $cacheItemPool->save($cacheItem)->shouldBeCalled();

        $dummy = new Dummy();
        $dummy->setId(1);

        $decoration = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoration->getIdentifiersFromItem($dummy)->shouldBeCalled()->willReturn(['id' => 1]);

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPool->reveal(), $decoration->reveal(), null);

        $this->assertEquals(['id' => 1], $identifiersExtractor->getIdentifiersFromItem($dummy));
    }

    public function testSecondPass()
    {
        $key = 'iri_identifiers'.md5(Dummy::class);

        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->shouldBeCalled()->willReturn(true);
        $cacheItem->get()->shouldBeCalled()->willReturn(['id']);

        $cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $cacheItemPool->getItem($key)->shouldBeCalled()->willReturn($cacheItem);

        $dummy = new Dummy();
        $dummy->setId(1);

        $decoration = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoration->getIdentifiersFromItem($dummy)->shouldNotBeCalled();

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPool->reveal(), $decoration->reveal(), null);

        $this->assertEquals(['id' => 1], $identifiersExtractor->getIdentifiersFromItem($dummy));
    }

    public function testSecondPassWithRelatedNotCached()
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

        $related = new RelatedDummy();
        $related->setId(1);

        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setRelatedDummy($related);

        $decoration = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoration->getIdentifiersFromItem($dummy)->shouldBeCalled()->willReturn(['id' => 1, 'relatedDummy' => 1]);

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPool->reveal(), $decoration->reveal(), null);

        $this->assertEquals(['id' => 1, 'relatedDummy' => 1], $identifiersExtractor->getIdentifiersFromItem($dummy));
    }

    public function testSecondPassWithRelatedCached()
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

        $related = new RelatedDummy();
        $related->setId(1);

        $dummy = new Dummy();
        $dummy->setId(1);
        $dummy->setRelatedDummy($related);

        $decoration = $this->prophesize(IdentifiersExtractorInterface::class);
        $decoration->getIdentifiersFromItem($dummy)->shouldNotBeCalled();

        $identifiersExtractor = new CachedIdentifiersExtractor($cacheItemPool->reveal(), $decoration->reveal(), null);

        $this->assertEquals(['id' => 1, 'relatedDummy' => 1], $identifiersExtractor->getIdentifiersFromItem($dummy));
    }
}
