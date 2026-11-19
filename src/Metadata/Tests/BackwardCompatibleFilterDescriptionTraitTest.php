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

use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use PHPUnit\Framework\TestCase;

final class BackwardCompatibleFilterDescriptionTraitTest extends TestCase
{
    public function testGetDescriptionReturnsEmptyArray(): void
    {
        $filter = new class {
            use BackwardCompatibleFilterDescriptionTrait;
        };

        $this->assertSame([], $filter->getDescription('Foo'));
    }
}
