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

namespace ApiPlatform\Serializer\Tests;

use ApiPlatform\Serializer\ResourceList;
use PHPUnit\Framework\TestCase;

class ResourceListTest extends TestCase
{
    private ResourceList $resourceList;

    protected function setUp(): void
    {
        $this->resourceList = new ResourceList();
    }

    public function testImplementsArrayObject(): void
    {
        $this->assertInstanceOf(\ArrayObject::class, $this->resourceList);
    }
}
