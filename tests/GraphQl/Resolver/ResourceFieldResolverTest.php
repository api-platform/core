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

namespace ApiPlatform\Core\Tests\GraphQl\Resolver;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\GraphQl\Resolver\ResourceFieldResolver;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\TestCase;

class ResourceFieldResolverTest extends TestCase
{
    public function testId()
    {
        $dummy = new Dummy();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummies/1')->shouldBeCalled();

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = 'id';
        $resolveInfo->fieldNodes = [];

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertEquals('/dummies/1', $resolver([ItemNormalizer::ITEM_KEY => serialize($dummy)], [], [], $resolveInfo));
    }

    public function testOriginalId()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = '_id';
        $resolveInfo->fieldNodes = [];

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertEquals(1, $resolver(['id' => 1], [], [], $resolveInfo));
    }

    public function testDirectAccess()
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $resolveInfo = new ResolveInfo([]);
        $resolveInfo->fieldName = 'foo';
        $resolveInfo->fieldNodes = [];

        $resolver = new ResourceFieldResolver($iriConverterProphecy->reveal());
        $this->assertEquals('bar', $resolver(['foo' => 'bar'], [], [], $resolveInfo));
        $this->assertEquals('bar', $resolver((object) ['foo' => 'bar'], [], [], $resolveInfo));
    }
}
