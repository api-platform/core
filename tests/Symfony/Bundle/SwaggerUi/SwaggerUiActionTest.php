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

namespace ApiPlatform\Tests\Symfony\Bundle\SwaggerUi;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Documentation\DocumentationInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\Symfony\Bundle\SwaggerUi\SwaggerUiAction;
use ApiPlatform\Symfony\Bundle\SwaggerUi\SwaggerUiContext;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Twig\Environment as TwigEnvironment;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @group legacy
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
     *
     * @param mixed $twigProphecy
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
                    'pkce' => false,
                ],
                'extraConfiguration' => [],
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
                    'pkce' => false,
                ],
                'extraConfiguration' => [],
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
                    'pkce' => false,
                ],
                'extraConfiguration' => [],
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
