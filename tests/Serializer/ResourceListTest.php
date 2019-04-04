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

namespace ApiPlatform\Core\Tests\Serializer;

use ApiPlatform\Core\Serializer\ResourceList;
use PHPUnit\Framework\TestCase;

class ResourceListTest extends TestCase
{
    /**
     * @var ResourceList
     */
    private $resourceList;

    protected function setUp()
    {
        $this->resourceList = new ResourceList();
    }

    public function testImplementsArrayObject()
    {
        $this->assertInstanceOf(\ArrayObject::class, $this->resourceList);
    }

    public function testSerialize()
    {
        $this->resourceList['foo'] = 'bar';

        $this->assertEquals('N;', serialize($this->resourceList));
    }
}
