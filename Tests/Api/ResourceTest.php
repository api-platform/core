<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Api;

use Dunglas\ApiBundle\Api\Resource;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testInitialize()
    {
        $resource = new Resource('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');

        $this->assertInstanceOf('Dunglas\ApiBundle\Api\ResourceInterface', $resource);
        $this->assertEquals('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity', $resource->getEntityClass());
        $this->assertEquals('DummyEntity', $resource->getShortName());
    }

    public function testCollectionOperations()
    {
        $operations = [$this->prophesize('Dunglas\ApiBundle\Api\Operation\OperationInterface')->reveal()];

        $resource = new Resource('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');
        $resource->initCollectionOperations($operations);

        $this->assertEquals($operations, $resource->getCollectionOperations());
    }

    public function testItemOperations()
    {
        $operations = [$this->prophesize('Dunglas\ApiBundle\Api\Operation\OperationInterface')->reveal()];

        $resource = new Resource('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');
        $resource->initItemOperations($operations);

        $this->assertEquals($operations, $resource->getItemOperations());
    }

    public function testFilters()
    {
        $filters = [$this->prophesize('Dunglas\ApiBundle\Api\Filter\FilterInterface')->reveal()];

        $resource = new Resource('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');
        $resource->initFilters($filters);

        $this->assertEquals($filters, $resource->getFilters());
    }

    public function testNormalizationContext()
    {
        $context = ['foo' => 'bar'];

        $resource = new Resource('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');
        $resource->initNormalizationContext($context);

        $contextWithResource = $context + ['resource' => $resource];
        $this->assertEquals($contextWithResource, $resource->getNormalizationContext());
        $this->assertNull($resource->getNormalizationGroups());

        $groups = ['a', 'b'];
        $contextWithGroups = ['foo' => 'bar', 'groups' => $groups];

        $resourceWithGroups = new Resource('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');
        $resourceWithGroups->initNormalizationContext($contextWithGroups);

        $contextWithGroupsAndResource = $contextWithGroups + ['resource' => $resourceWithGroups];
        $this->assertEquals($contextWithGroupsAndResource, $resourceWithGroups->getNormalizationContext());
        $this->assertEquals($groups, $resourceWithGroups->getNormalizationGroups());
    }

    public function testDenormalizationContext()
    {
        $context = ['foo' => 'bar'];

        $resource = new Resource('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');
        $resource->initDenormalizationContext($context);

        $contextWithResource = $context + ['resource' => $resource];
        $this->assertEquals($contextWithResource, $resource->getDenormalizationContext());
        $this->assertNull($resource->getDenormalizationGroups());

        $groups = ['a', 'b'];
        $contextWithGroups = ['foo' => 'bar', 'groups' => $groups];

        $resourceWithGroups = new Resource('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');
        $resourceWithGroups->initDenormalizationContext($contextWithGroups);

        $contextWithGroupsAndResource = $contextWithGroups + ['resource' => $resourceWithGroups];
        $this->assertEquals($contextWithGroupsAndResource, $resourceWithGroups->getDenormalizationContext());
        $this->assertEquals($groups, $resourceWithGroups->getDenormalizationGroups());
    }

    public function testValidationGroups()
    {
        $groups = ['a', 'b'];

        $resource = new Resource('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');

        $resource->initValidationGroups($groups);
        $this->assertEquals($groups, $resource->getValidationGroups());
    }

    public function testShortName()
    {
        $resource = new Resource('Dunglas\ApiBundle\Tests\Fixtures\DummyEntity');
        $resource->initShortName('Test');

        $this->assertEquals('Test', $resource->getShortName());
    }

    /**
     * @expectedException \Dunglas\ApiBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage The class "Foo\Bar" does not exist.
     */
    public function testEntityNotExist()
    {
        new Resource('Foo\Bar');
    }
}
