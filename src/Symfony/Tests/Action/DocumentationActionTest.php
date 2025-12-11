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

namespace ApiPlatform\Symfony\Tests\Action;

use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Action\DocumentationAction;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class DocumentationActionTest extends TestCase
{
    use ProphecyTrait;

    public function testHtmlFormatWhenSwaggerUiDisabledThrows404(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Swagger UI is disabled.');

        $request = new Request();
        $request->attributes->set('_format', 'html');

        $openApiFactory = $this->createMock(OpenApiFactoryInterface::class);
        $resourceNameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);

        $documentation = new DocumentationAction(
            $resourceNameCollectionFactory,
            openApiFactory: $openApiFactory,
            documentationFormats: [
                'json' => ['application/json'],
                'html' => ['text/html'],
            ],
            swaggerUiEnabled: false,
        );

        $documentation($request);
    }

    public function testJsonFormatWhenSwaggerUiDisabledIsAccessible(): void
    {
        $request = new Request();
        $request->attributes->set('_format', 'json');

        $openApiFactory = $this->createMock(OpenApiFactoryInterface::class);
        $openApiFactory->expects($this->once())->method('__invoke')->willReturn(new OpenApi(new Info('title', '1.0.0'), [], new Paths()));

        $resourceNameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);

        $documentation = new DocumentationAction(
            $resourceNameCollectionFactory,
            openApiFactory: $openApiFactory,
            swaggerUiEnabled: false
        );

        $result = $documentation($request);
        $this->assertInstanceOf(OpenApi::class, $result);
    }

    public function testDocumentationAction(): void
    {
        $openApi = new OpenApi(new Info('my api', '1.0.0'), [], new Paths());
        $openApiFactoryProphecy = $this->prophesize(OpenApiFactoryInterface::class);
        $openApiFactoryProphecy->__invoke(Argument::any())->shouldBeCalled()->willReturn($openApi);
        $documentation = new DocumentationAction($this->prophesize(ResourceNameCollectionFactoryInterface::class)->reveal(), 'my api', '', '1.0.0', $openApiFactoryProphecy->reveal());
        $this->assertInstanceOf(OpenApi::class, $documentation(
            new Request(query: ['api_gateway' => true, 'spec_version' => '3.1.0'], server: ['REQUEST_URI' => '/api'], attributes: ['_format' => null, '_api_normalization_context' => ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => '3.1.0']])
        ));
    }

    public function testDocumentationActionWithoutOpenApiFactory(): void
    {
        $openApiFactoryProphecy = $this->prophesize(OpenApiFactoryInterface::class);
        $openApiFactoryProphecy->__invoke(Argument::any())->shouldNotBeCalled();
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']))->shouldBeCalled();

        $documentation = new DocumentationAction($resourceNameCollectionFactoryProphecy->reveal(), 'my api', '', '1.0.0');
        $this->assertInstanceOf(Documentation::class, $documentation(new Request(query: ['api_gateway' => true, 'spec_version' => '3.1.0'], server: ['REQUEST_URI' => '/api'], attributes: ['_format' => null, '_api_normalization_context' => ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => '3.1.0']])));
    }

    public static function getOpenApiContentTypes(): array
    {
        return [['application/json'], ['application/html']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getOpenApiContentTypes')]
    public function testGetOpenApi($contentType): void
    {
        $request = new Request(server: ['CONTENT_TYPE' => $contentType]);
        $openApiFactory = $this->createMock(OpenApiFactoryInterface::class);
        $resourceNameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')->willReturnCallback(fn ($operation, $uriVariables, $context) => new OpenApi(new Info('title', '1.0.0'), [], new Paths()));
        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->willReturnArgument(0);
        $entrypoint = new DocumentationAction($resourceNameCollectionFactory, provider: $provider, processor: $processor, openApiFactory: $openApiFactory);
        $entrypoint($request);
    }

    public function testGetHydraDocumentation(): void
    {
        $request = new Request();
        $resourceNameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactory->expects($this->once())->method('create')->willReturn(new ResourceNameCollection([]));
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')->willReturnCallback(fn ($operation, $uriVariables, $context) => $operation->getProvider()(...\func_get_args()));
        $processor = $this->createMock(ProcessorInterface::class);
        $processor->expects($this->once())->method('process')->willReturnArgument(0);
        $entrypoint = new DocumentationAction($resourceNameCollectionFactory, provider: $provider, processor: $processor);
        $entrypoint($request);
    }

    public function testHtmlFormatNotSupportedThrowsException(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Format "html" is not supported');

        $request = new Request();
        $request->attributes->set('_format', 'html');

        $openApiFactory = $this->createMock(OpenApiFactoryInterface::class);
        $resourceNameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);

        $documentation = new DocumentationAction(
            $resourceNameCollectionFactory,
            openApiFactory: $openApiFactory,
            documentationFormats: ['json' => ['application/json']],
            swaggerUiEnabled: false,
        );

        $documentation($request);
    }

    public function testHtmlFormatWhenDocsDisabledThrows404(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $request = new Request();
        $request->attributes->set('_format', 'html');

        $openApiFactory = $this->createMock(OpenApiFactoryInterface::class);
        $resourceNameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);

        $documentation = new DocumentationAction(
            $resourceNameCollectionFactory,
            openApiFactory: $openApiFactory,
            documentationFormats: [
                'html' => ['text/html'],
            ],
            docsEnabled: false,
        );

        $documentation($request);
    }
}
