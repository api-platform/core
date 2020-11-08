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
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment as TwigEnvironment;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SwaggerUiActionTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    public const SPEC = [
        'paths' => [
            '/fs' => ['get' => ['operationId' => 'getFCollection']],
            '/fs/{id}' => ['get' => ['operationId' => 'getFItem']],
        ],
    ];

    /**
     * @dataProvider getInvokeParameters
     * @group legacy
     */
    public function testInvoke(Request $request, $twigProphecy)
    {
        $this->expectDeprecation('The use of "ApiPlatform\Core\Bridge\Symfony\Bundle\Action\SwaggerUiAction" is deprecated since API Platform 2.6, use "ApiPlatform\Core\Bridge\Symfony\Bundle\SwaggerUi\SwaggerUiAction" instead.');
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
        $twigCollectionProphecy = $this->prophesize(TwigEnvironment::class);
        $twigCollectionProphecy->render('@ApiPlatform/SwaggerUi/index.html.twig', [
            'title' => '',
            'description' => '',
            'formats' => [],
            'showWebby' => true,
            'swaggerUiEnabled' => false,
            'reDocEnabled' => false,
            'graphqlEnabled' => false,
            'graphiQlEnabled' => false,
            'graphQlPlaygroundEnabled' => false,
            'assetPackage' => null,
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
        ])->shouldBeCalled()->willReturn('');

        $twigItemProphecy = $this->prophesize(TwigEnvironment::class);
        $twigItemProphecy->render('@ApiPlatform/SwaggerUi/index.html.twig', [
            'title' => '',
            'description' => '',
            'formats' => [],
            'swaggerUiEnabled' => false,
            'showWebby' => true,
            'reDocEnabled' => false,
            'graphqlEnabled' => false,
            'graphiQlEnabled' => false,
            'graphQlPlaygroundEnabled' => false,
            'assetPackage' => null,
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
        ])->shouldBeCalled()->willReturn('');

        return [
            [new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get']), $twigCollectionProphecy],
            [new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get']), $twigItemProphecy],
            [new Request([], [], ['_api_resource_class' => 'Foo', '_api_item_operation_name' => 'get'], [], [], ['REQUEST_URI' => '/docs', 'SCRIPT_FILENAME' => '/docs']), $twigItemProphecy],
        ];
    }

    /**
     * @dataProvider getDoNotRunCurrentRequestParameters
     * @group legacy
     */
    public function testDoNotRunCurrentRequest(Request $request)
    {
        $this->expectDeprecation('The use of "ApiPlatform\Core\Bridge\Symfony\Bundle\Action\SwaggerUiAction" is deprecated since API Platform 2.6, use "ApiPlatform\Core\Bridge\Symfony\Bundle\SwaggerUi\SwaggerUiAction" instead.');
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['Foo', 'Bar']))->shouldBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata());

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $normalizerProphecy->normalize(Argument::type(Documentation::class), 'json', Argument::type('array'))->willReturn(self::SPEC)->shouldBeCalled();

        $twigProphecy = $this->prophesize(TwigEnvironment::class);
        $twigProphecy->render('@ApiPlatform/SwaggerUi/index.html.twig', [
            'title' => '',
            'description' => '',
            'formats' => [],
            'showWebby' => true,
            'swaggerUiEnabled' => false,
            'reDocEnabled' => false,
            'graphqlEnabled' => false,
            'graphiQlEnabled' => false,
            'graphQlPlaygroundEnabled' => false,
            'assetPackage' => null,
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
        ])->shouldBeCalled()->willReturn('');

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

    public function getDoNotRunCurrentRequestParameters(): iterable
    {
        $nonSafeRequest = new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'post']);
        $nonSafeRequest->setMethod('POST');
        yield [$nonSafeRequest];
        yield [new Request()];
    }
}
