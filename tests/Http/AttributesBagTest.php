<?php

/*
 *  This file is part of the API Platform project.
 *
 *  (c) Kévin Dunglas <dunglas@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Http;

use ApiPlatform\Core\Http\AttributesBag;

/**
 * @covers ApiPlatform\Core\Http\AttributesBag
 *
 * @author             Théo FIDRY <theo.fidry@gmail.com>
 */
class AttributesBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideArguments
     */
    public function testConstruct($resourceClass, $collectionOperationName, $itemOperationName, $format)
    {
        $bag = new AttributesBag($resourceClass, $collectionOperationName, $itemOperationName, $format);

        $this->assertEquals($resourceClass, $bag->getResourceClass());
        $this->assertEquals($collectionOperationName, $bag->getCollectionOperationName());
        $this->assertEquals($itemOperationName, $bag->getItemOperationName());
        $this->assertEquals($format, $bag->getFormat());
    }

    public function provideArguments()
    {
        yield [
            'App\Entity\Dummy',
            'get',
            'post',
            'jsonld',
        ];

        yield [
            'App\Entity\Dummy',
            'delete',
            null,
            'xml',
        ];

        yield [
            'App\Entity\Dummy',
            null,
            'put',
            'json',
        ];
    }
}
