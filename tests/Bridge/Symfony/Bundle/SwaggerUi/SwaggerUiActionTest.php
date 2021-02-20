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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\SwaggerUi;

use ApiPlatform\Core\Bridge\Symfony\Bundle\SwaggerUi\SwaggerUiAction;
use ApiPlatform\Core\Bridge\Symfony\Bundle\SwaggerUi\SwaggerUiContext;
use ApiPlatform\Core\Documentation\DocumentationInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model\Info;
use ApiPlatform\Core\OpenApi\Model\Paths;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\OpenApi\Options;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment as TwigEnvironment;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class SwaggerUiActionTest extends TestCase
{
    use ProphecyTrait;

    public const SPEC = [
        'paths' => [
            '/fs' => ['get' => ['operationId' => 'getFCollection']],
            '/fs/{id}' => ['get' => ['operationId' => 'getFItem']],
        ],
    ];

    /**
     * @dataProvider getInvokeParameters
     */
    public function testInvoke(Request $request, $twigProphecy)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata('F'))->shouldBeCalled();

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $normalizerProphecy->normalize(Argument::type(DocumentationInterface::class), 'json', Argument::type('array'))->willReturn(self::SPEC)->shouldBeCalled();

        $urlGeneratorProphecy = $this->prophesize(UrlGenerator::class);
        $urlGeneratorProphecy->generate('api_doc', ['format' => 'json'])->willReturn('/url')->shouldBeCalled();

        $openApiFactoryProphecy = $this->prophesize(OpenApiFactoryInterface::class);
        $openApiFactoryProphecy->__invoke(Argument::type('array'))->willReturn(new OpenApi(new Info('title', '1.0.0'), [], new Paths()))->shouldBeCalled();

        $action = new SwaggerUiAction(
            $resourceMetadataFactoryProphecy->reveal(),
            $twigProphecy->reveal(),
            $urlGeneratorProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $openApiFactoryProphecy->reveal(),
            new Options('title', '', '1.0.0'),
            new SwaggerUiContext(),
            ['jsonld' => ['application/ld+json']]
        );
        $action($request);
    }

    public function getInvokeParameters()
    {
        $twigCollectionProphecy = $this->prophesize(TwigEnvironment::class);
        $twigCollectionProphecy->render('@ApiPlatform/SwaggerUi/index.html.twig', [
            'title' => 'title',
            'description' => '',
            'formats' => ['jsonld' => ['application/ld+json']],
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
            'title' => 'title',
            'description' => '',
            'formats' => ['jsonld' => ['application/ld+json']],
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
                    'clientId' => null,
                    'clientSecret' => null,
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
     */
    public function testDoNotRunCurrentRequest(Request $request)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata());

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $normalizerProphecy->normalize(Argument::type(DocumentationInterface::class), 'json', Argument::type('array'))->willReturn(self::SPEC)->shouldBeCalled();

        $twigProphecy = $this->prophesize(TwigEnvironment::class);
        $twigProphecy->render('@ApiPlatform/SwaggerUi/index.html.twig', [
            'title' => 'title',
            'description' => '',
            'formats' => ['jsonld' => ['application/ld+json']],
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

        $openApiFactoryProphecy = $this->prophesize(OpenApiFactoryInterface::class);
        $openApiFactoryProphecy->__invoke(Argument::type('array'))->willReturn(new OpenApi(new Info('title', '1.0.0'), [], new Paths()))->shouldBeCalled();

        $action = new SwaggerUiAction(
            $resourceMetadataFactoryProphecy->reveal(),
            $twigProphecy->reveal(),
            $urlGeneratorProphecy->reveal(),
            $normalizerProphecy->reveal(),
            $openApiFactoryProphecy->reveal(),
            new Options('title', '', '1.0.0'),
            new SwaggerUiContext(),
            ['jsonld' => ['application/ld+json']]
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
