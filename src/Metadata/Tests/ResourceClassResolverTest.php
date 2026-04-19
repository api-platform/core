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

namespace ApiPlatform\Metadata\Tests;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\ResourceClassResolver;
use PHPUnit\Framework\TestCase;

class ResourceClassResolverTest extends TestCase
{
    public function testReset(): void
    {
        $resourceNameCollectionFactoryProphecy = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->method('create')->willReturn(new ResourceNameCollection([ \stdClass::class ]));

        $resolver = new ResourceClassResolver($resourceNameCollectionFactoryProphecy);

        $this->assertTrue($resolver->isResourceClass(\stdClass::class));

        $resolver->reset();

        $refl = new \ReflectionClass($resolver);
        $localIsResourceClassCache = $refl->getProperty('localIsResourceClassCache');
        $localIsResourceClassCache->setAccessible(true);
        $localMostSpecificResourceClassCache = $refl->getProperty('localMostSpecificResourceClassCache');
        $localMostSpecificResourceClassCache->setAccessible(true);

        $this->assertEmpty($localIsResourceClassCache->getValue($resolver));
        $this->assertEmpty($localMostSpecificResourceClassCache->getValue($resolver));
    }
}
