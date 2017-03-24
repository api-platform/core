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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Action;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Action\SwaggerUiAction;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use Prophecy\Argument;
use Prophecy\Prophecy\ProphecyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SwaggerUiActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getInvokeParameters
     */
    public function testInvoke(Request $request, ProphecyInterface $twigProphecy)
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['Foo', 'Bar']))->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata('F'))->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::type(Documentation::class), 'json')->willReturn('hello')->shouldBeCalled();

        $action = new SwaggerUiAction(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            $twigProphecy->reveal()
        );
        $action($request);
    }

    public function getInvokeParameters()
    {
        $postRequest = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'post']);
        $postRequest->setMethod('POST');

        $twigCollectionProphecy = $this->prophesize(\Twig_Environment::class);
        $twigCollectionProphecy->render('ApiPlatformBundle:SwaggerUi:index.html.twig', [
            'spec' => 'hello',
            'shortName' => 'F',
            'operationId' => 'getFCollection',
            'title' => '',
            'description' => '',
            'formats' => [],
        ])->shouldBeCalled();

        $twigItemProphecy = $this->prophesize(\Twig_Environment::class);
        $twigItemProphecy->render('ApiPlatformBundle:SwaggerUi:index.html.twig', [
            'spec' => 'hello',
            'shortName' => 'F',
            'operationId' => 'getFItem',
            'title' => '',
            'description' => '',
            'formats' => [],
        ])->shouldBeCalled();

        return [
            [new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']), $twigCollectionProphecy],
            [new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']), $twigItemProphecy],
        ];
    }

    /**
     * @dataProvider getDoNotRunCurrentRequestParameters
     */
    public function testDoNotRunCurrentRequest(Request $request)
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['Foo', 'Bar']))->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize(Argument::type(Documentation::class), 'json')->willReturn('hello')->shouldBeCalled();

        $twigProphecy = $this->prophesize(\Twig_Environment::class);
        $twigProphecy->render('ApiPlatformBundle:SwaggerUi:index.html.twig', [
            'spec' => 'hello',
            'shortName' => null,
            'operationId' => null,
            'title' => '',
            'description' => '',
            'formats' => [],
        ])->shouldBeCalled();

        $action = new SwaggerUiAction(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $serializerProphecy->reveal(),
            $twigProphecy->reveal()
        );
        $action($request);
    }

    public function getDoNotRunCurrentRequestParameters()
    {
        $nonSafeRequest = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post']);
        $nonSafeRequest->setMethod('POST');

        return [
            [$nonSafeRequest],
            [new Request()],
        ];
    }
}
