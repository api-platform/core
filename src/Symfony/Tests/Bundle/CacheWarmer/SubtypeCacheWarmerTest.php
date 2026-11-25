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

namespace ApiPlatform\Symfony\Tests\Bundle\CacheWarmer;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\SubtypeResolverInterface;
use ApiPlatform\Symfony\Bundle\CacheWarmer\SubtypeCacheWarmer;
use PHPUnit\Framework\TestCase;

class SubtypeCacheWarmerTest extends TestCase
{
    public function testSubtypeNameClashFailsTheWarmup(): void
    {
        $resourceNameCollectionFactory = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->method('create')->willReturn(new ResourceNameCollection(['Vehicle']));

        $subtypeResolver = $this->createMock(SubtypeResolverInterface::class);
        $subtypeResolver->expects($this->once())->method('getSubtypes')->with('Vehicle')->willReturn(['Car']);
        $subtypeResolver->expects($this->once())->method('getTypes')->with('Car')->willThrowException(new RuntimeException('clash'));

        $warmer = new SubtypeCacheWarmer($subtypeResolver, $resourceNameCollectionFactory);

        $this->expectException(RuntimeException::class);
        $warmer->warmUp('/tmp');
    }
}
