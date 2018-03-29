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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\ItemExtensions;
use PHPUnit\Framework\TestCase;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class ItemExtensionsTest extends TestCase
{
    public function testGetItemExtensions()
    {
        $extension = $this->prophesize(QueryItemExtensionInterface::class);
        $queryextension = $this->prophesize(QueryResultItemExtensionInterface::class);

        $collectionExtensions = new ItemExtensions([$extension->reveal(), $queryextension->reveal()]);
        foreach ($collectionExtensions as $i => $extension) {
            if (0 === $i) {
                $this->assertTrue($extension instanceof QueryItemExtensionInterface);
                continue;
            }

            $this->assertTrue($extension instanceof QueryResultItemExtensionInterface);
        }
    }
}
