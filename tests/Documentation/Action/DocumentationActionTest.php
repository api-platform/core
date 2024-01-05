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

namespace ApiPlatform\Tests\Documentation\Action;

use ApiPlatform\Documentation\Action\DocumentationAction;
use ApiPlatform\Documentation\Documentation;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class DocumentationActionTest extends TestCase
{
    use ProphecyTrait;

    public function testDocumentationAction(): void
    {
        $openApi = new OpenApi(new Info('my api', '1.0.0'), [], new Paths());
        $openApiFactoryProphecy = $this->prophesize(OpenApiFactoryInterface::class);
        $openApiFactoryProphecy->__invoke(Argument::any())->shouldBeCalled()->willReturn($openApi);
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getMimeType('json')->willReturn('application/json');
        $requestProphecy->getRequestFormat('')->willReturn('json');
        $attributesProphecy = $this->prophesize(ParameterBagInterface::class);
        $queryProphecy = $this->prophesize(ParameterBag::class);
        $requestProphecy->attributes = $attributesProphecy->reveal();
        $requestProphecy->query = $queryProphecy->reveal();
        $requestProphecy->headers = $this->prophesize(ParameterBagInterface::class)->reveal();
        $requestProphecy->getBaseUrl()->willReturn('/api')->shouldBeCalledTimes(1);
        $queryProphecy->getBoolean('api_gateway')->willReturn(true)->shouldBeCalledTimes(1);
        $queryProphecy->get('spec_version')->willReturn('3.1.0')->shouldBeCalledTimes(1);
        $attributesProphecy->get('_api_normalization_context', [])->willReturn(['foo' => 'bar'])->shouldBeCalledTimes(1);
        $attributesProphecy->get('_format')->willReturn(null)->shouldBeCalledTimes(1);
        $attributesProphecy->set('_api_normalization_context', ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => '3.1.0'])->shouldBeCalledTimes(1);

        $documentation = new DocumentationAction($this->prophesize(ResourceNameCollectionFactoryInterface::class)->reveal(), 'my api', '', '1.0.0', $openApiFactoryProphecy->reveal());
        $this->assertInstanceOf(OpenApi::class, $documentation($requestProphecy->reveal()));
    }

    public function testDocumentationActionWithoutOpenApiFactory(): void
    {
        $openApiFactoryProphecy = $this->prophesize(OpenApiFactoryInterface::class);
        $openApiFactoryProphecy->__invoke(Argument::any())->shouldNotBeCalled();
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getRequestFormat('')->willReturn('json');
        $requestProphecy->headers = $this->prophesize(ParameterBagInterface::class)->reveal();
        $requestProphecy->getMimeType('json')->willReturn('application/json');
        $attributesProphecy = $this->prophesize(ParameterBagInterface::class);
        $attributesProphecy->get('_format')->willReturn(null)->shouldBeCalledTimes(1);
        $queryProphecy = $this->prophesize(ParameterBag::class);
        $requestProphecy->attributes = $attributesProphecy->reveal();
        $requestProphecy->query = $queryProphecy->reveal();
        $requestProphecy->getBaseUrl()->willReturn('/api')->shouldBeCalledTimes(1);
        $queryProphecy->getBoolean('api_gateway')->willReturn(true)->shouldBeCalledTimes(1);
        $queryProphecy->get('spec_version')->willReturn('3.1.0')->shouldBeCalledTimes(1);
        $attributesProphecy->get('_api_normalization_context', [])->willReturn(['foo' => 'bar'])->shouldBeCalledTimes(1);
        $attributesProphecy->set('_api_normalization_context', ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => '3.1.0'])->shouldBeCalledTimes(1);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']))->shouldBeCalled();

        $documentation = new DocumentationAction($resourceNameCollectionFactoryProphecy->reveal(), 'my api', '', '1.0.0');
        $this->assertInstanceOf(Documentation::class, $documentation($requestProphecy->reveal()));
    }

    public static function getOpenApiContentTypes(): array
    {
        return [['application/json'], ['application/html']];
    }

    /**
     * @dataProvider getOpenApiContentTypes
     */
    public function testGetOpenApi($contentType): void
    {
        $request = new Request(server: ['CONTENT_TYPE' => $contentType]);
        $openApiFactory = $this->createMock(OpenApiFactoryInterface::class);
        $openApiFactory->expects($this->once())->method('__invoke')->willReturn(new OpenApi(new Info('a', 'v'), [], new Paths()));
        $resourceNameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')->willReturnCallback(fn ($operation, $uriVariables, $context) => $operation->getProvider()(...\func_get_args()));
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
}
