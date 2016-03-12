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

use ApiPlatform\Core\Http\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @covers ApiPlatform\Core\Http\RequestAttributesExtractor
 *
 * @author             Théo FIDRY <theo.fidry@gmail.com>
 */
class RequestAttributesExtractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestAttributesExtractor
     */
    private $extractor;

    public function setUp()
    {
        $this->extractor = new RequestAttributesExtractor();
    }

    /**
     * @dataProvider provideValidRequest
     */
    public function testExtractFromValidRequest($request, $expected)
    {
        $actual = $this->extractor->extract($request);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider provideInvalidRequest
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     */
    public function testExtractFromInvalidRequest($request)
    {
        $this->extractor->extract($request);
    }

    public function provideValidRequest()
    {
        $resourceClass = 'App\Entity\Dummy';
        $collectionOperationName = 'get';
        $itemOperationName = null;
        $format = 'jsonld';

        $parameterBagProphecy = $this->prophesize(ParameterBag::class);
        $parameterBagProphecy->get('_resource_class')->shouldBeCalledTimes(1);
        $parameterBagProphecy->get('_resource_class')->willReturn($resourceClass);
        $parameterBagProphecy->get('_collection_operation_name')->shouldBeCalledTimes(1);
        $parameterBagProphecy->get('_collection_operation_name')->willReturn($collectionOperationName);
        $parameterBagProphecy->get('_item_operation_name')->shouldBeCalledTimes(1);
        $parameterBagProphecy->get('_item_operation_name')->willReturn($itemOperationName);
        $parameterBagProphecy->get('_api_format')->shouldBeCalledTimes(1);
        $parameterBagProphecy->get('_api_format')->willReturn($format);
        /* @var ParameterBag $parameterBag */
        $parameterBag = $parameterBagProphecy->reveal();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->initialize(Argument::cetera())
    }

    public function provideInvalidRequest()
    {
        //TODO
    }
}
