<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Annotation;

use Dunglas\ApiBundle\Annotation\Resource;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testAssignation()
    {
        $resource = new Resource();
        $resource->shortName = 'shortName';
        $resource->description = 'description';
        $resource->iri = 'http://example.com/res';
        $resource->itemOperations = ['foo' => ['bar']];
        $resource->collectionOperations = ['bar' => ['foo']];
        $resource->attributes = ['foo' => 'bar'];

        $this->assertEquals('shortName', $resource->shortName);
        $this->assertEquals('description', $resource->description);
        $this->assertEquals('http://example.com/res', $resource->iri);
        $this->assertEquals(['bar' => ['foo']], $resource->collectionOperations);
        $this->assertEquals(['foo' => 'bar'], $resource->attributes);
    }
}
