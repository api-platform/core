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

use Dunglas\ApiBundle\Annotation\Property;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testAssignation()
    {
        $property = new Property();
        $property->description = 'description';
        $property->readable = true;
        $property->writable = true;
        $property->readableLink = true;
        $property->writableLink = true;
        $property->required = true;
        $property->iri = 'http://example.com/prop';
        $property->identifier = true;
        $property->attributes = ['foo' => 'bar'];

        $this->assertEquals('description', $property->description);
        $this->assertTrue($property->readable);
        $this->assertTrue($property->writable);
        $this->assertTrue($property->readableLink);
        $this->assertTrue($property->writableLink);
        $this->assertTrue($property->required);
        $this->assertEquals('http://example.com/prop', $property->iri);
        $this->assertTrue($property->identifier);
        $this->assertEquals(['foo' => 'bar'], $property->attributes);
    }
}
