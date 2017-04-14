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

namespace ApiPlatform\Core\Tests\Annotation;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testAssignation()
    {
        $resource = new ApiResource();
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
