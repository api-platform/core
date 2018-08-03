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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Action;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Action\SwaggerUiAction;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ProphecyInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SwaggerUiActionTest extends TestCase
{
    const SPEC = [
        'paths' => [
            '/fs' => ['get' => ['operationId' => 'getFCollection']],
            '/fs/{id}' => ['get' => ['operationId' => 'getFItem']],
        ],
    ];

    /**
     * @dataProvider getInvokeParameters
     */
    public function testInvoke(Request $request, ProphecyInterface $twigProphecy)
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['Foo', 'Bar']))->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata('F'))->shouldBeCalled();

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $normalizerProphecy->normalize(Argument::type(Documentation::class), 'json', Argument::type('array'))->willReturn(self::SPEC)->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGenerator::class);
        $urlGeneratorProphecy->generate('api_doc', ['format' => 'json'])->willReturn('/url')->shouldBeCalled();

        $action = new SwaggerUiAction(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $twigProphecy->reveal(),
            $urlGeneratorProphecy->reveal()
        );
        $action($request);
    }

    public function getInvokeParameters()
    {
        $postRequest = new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'post']);
        $postRequest->setMethod('POST');

        $twigCollectionProphecy = $this->prophesize(\Twig_Environment::class);
        $twigCollectionProphecy->render('@ApiPlatform/SwaggerUi/index.html.twig', [
            'title' => '',
            'description' => '',
            'formats' => [],
            'swagger_data' => [
                'url' => '/url',
                'spec' => self::SPEC,
                'oauth' => [
                    'enabled' => false,
                    'clientId' => '',
                    'clientSecret' => '',
                    'type' => '',
                    'flow' => '',
                    'tokenUrl' => '',
                    'authorizationUrl' => '',
                    'scopes' => [],
                ],
                'shortName' => 'F',
                'operationId' => 'getFCollection',
                'id' => null,
                'queryParameters' => [],
                'path' => '/fs',
                'method' => 'get',
            ],
        ])->shouldBeCalled();

        $twigItemProphecy = $this->prophesize(\Twig_Environment::class);
        $twigItemProphecy->render('@ApiPlatform/SwaggerUi/index.html.twig', [
            'title' => '',
            'description' => '',
            'formats' => [],
            'swagger_data' => [
                'url' => '/url',
                'spec' => self::SPEC,
                'oauth' => [
                    'enabled' => false,
                    'clientId' => '',
                    'clientSecret' => '',
                    'type' => '',
                    'flow' => '',
                    'tokenUrl' => '',
                    'authorizationUrl' => '',
                    'scopes' => [],
                ],
                'shortName' => 'F',
                'operationId' => 'getFItem',
                'id' => null,
                'queryParameters' => [],
                'path' => '/fs/{id}',
                'method' => 'get',
            ],
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

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $normalizerProphecy->normalize(Argument::type(Documentation::class), 'json', Argument::type('array'))->willReturn(self::SPEC)->shouldBeCalled();

        $twigProphecy = $this->prophesize(\Twig_Environment::class);
        $twigProphecy->render('@ApiPlatform/SwaggerUi/index.html.twig', [
            'title' => '',
            'description' => '',
            'formats' => [],
            'swagger_data' => [
                'url' => '/url',
                'spec' => self::SPEC,
                'oauth' => [
                    'enabled' => false,
                    'clientId' => '',
                    'clientSecret' => '',
                    'type' => '',
                    'flow' => '',
                    'tokenUrl' => '',
                    'authorizationUrl' => '',
                    'scopes' => [],
                ],
            ],
        ])->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGenerator::class);
        $urlGeneratorProphecy->generate('api_doc', ['format' => 'json'])->willReturn('/url')->shouldBeCalled();

        $action = new SwaggerUiAction(
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $twigProphecy->reveal(),
            $urlGeneratorProphecy->reveal()
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
