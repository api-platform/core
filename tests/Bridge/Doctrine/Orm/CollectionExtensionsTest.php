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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm;

use ApiPlatform\Core\Bridge\Doctrine\Orm\CollectionExtensions;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class CollectionExtensionsTest extends TestCase
{
    public function testGetCollectionExtensions()
    {
        $extension = $this->prophesize(QueryCollectionExtensionInterface::class);
        $queryextension = $this->prophesize(QueryResultCollectionExtensionInterface::class);

        $collectionExtensions = new CollectionExtensions([$extension->reveal(), $queryextension->reveal()]);
        foreach ($collectionExtensions as $i => $extension) {
            if (0 === $i) {
                $this->assertTrue($extension instanceof QueryCollectionExtensionInterface);
                continue;
            }

            $this->assertTrue($extension instanceof QueryResultCollectionExtensionInterface);
        }
    }
}
