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

namespace ApiPlatform\Core\Tests\Documentation\Action;

use ApiPlatform\Core\Api\FormatsProviderInterface;
use ApiPlatform\Core\Documentation\Action\DocumentationAction;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model\Info;
use ApiPlatform\Core\OpenApi\Model\Paths;
use ApiPlatform\Core\OpenApi\OpenApi;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class DocumentationActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @group legacy
     * @expectedDeprecation Not passing an instance of "ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface" as 7th parameter of the constructor of "ApiPlatform\Core\Documentation\Action\DocumentationAction" is deprecated since API Platform 2.6
     */
    public function testDocumentationAction(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getRequestFormat()->willReturn('json');
        $attributesProphecy = $this->prophesize(ParameterBagInterface::class);
        $queryProphecy = $this->prophesize(ParameterBag::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']));
        $requestProphecy->attributes = $attributesProphecy->reveal();
        $requestProphecy->query = $queryProphecy->reveal();
        $requestProphecy->getBaseUrl()->willReturn('/api')->shouldBeCalledTimes(1);
        $queryProphecy->getBoolean('api_gateway')->willReturn(true)->shouldBeCalledTimes(1);
        $queryProphecy->getInt('spec_version', 2)->willReturn(2)->shouldBeCalledTimes(1);
        $attributesProphecy->all()->willReturn(['_api_normalization_context' => ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => 2]])->shouldBeCalledTimes(1);
        $attributesProphecy->get('_api_normalization_context', [])->willReturn(['foo' => 'bar'])->shouldBeCalledTimes(1);
        $attributesProphecy->set('_api_normalization_context', ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => 2])->shouldBeCalledTimes(1);

        $documentation = new DocumentationAction($resourceNameCollectionFactoryProphecy->reveal(), 'My happy hippie api', 'lots of chocolate', '1.0.0');
        $this->assertEquals(new Documentation(new ResourceNameCollection(['dummies']), 'My happy hippie api', 'lots of chocolate', '1.0.0'), $documentation($requestProphecy->reveal()));
    }

    public function testLegacyDocumentationAction(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getRequestFormat()->willReturn('json');
        $attributesProphecy = $this->prophesize(ParameterBagInterface::class);
        $queryProphecy = $this->prophesize(ParameterBag::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']));
        $requestProphecy->attributes = $attributesProphecy->reveal();
        $requestProphecy->query = $queryProphecy->reveal();
        $requestProphecy->getBaseUrl()->willReturn('/api')->shouldBeCalledTimes(1);
        $queryProphecy->getBoolean('api_gateway')->willReturn(true)->shouldBeCalledTimes(1);
        $queryProphecy->getInt('spec_version', 2)->willReturn(2)->shouldBeCalledTimes(1);
        $attributesProphecy->all()->willReturn(['_api_normalization_context' => ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => 2]])->shouldBeCalledTimes(1);
        $attributesProphecy->get('_api_normalization_context', [])->willReturn(['foo' => 'bar'])->shouldBeCalledTimes(1);
        $attributesProphecy->set('_api_normalization_context', ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => 2])->shouldBeCalledTimes(1);
        $formatsProviderProphecy = $this->prophesize(FormatsProviderInterface::class);
        $formatsProviderProphecy->getFormatsFromAttributes(Argument::type('array'))->willReturn(['formats' => ['jsonld' => 'application/ld+json']])->shouldBeCalled();

        $documentation = new DocumentationAction($resourceNameCollectionFactoryProphecy->reveal(), 'My happy hippie api', 'lots of chocolate', '1.0.0', $formatsProviderProphecy->reveal());
        $this->assertEquals(new Documentation(new ResourceNameCollection(['dummies']), 'My happy hippie api', 'lots of chocolate', '1.0.0', ['formats' => ['jsonld' => 'application/ld+json']]), $documentation($requestProphecy->reveal()));
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing an array or an instance of "ApiPlatform\Core\Api\FormatsProviderInterface" as 5th parameter of the constructor of "ApiPlatform\Core\Documentation\Action\DocumentationAction" is deprecated since API Platform 2.5
     */
    public function testDocumentationActionFormatDeprecation()
    {
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']));
        new DocumentationAction($resourceNameCollectionFactoryProphecy->reveal(), '', '', '', ['formats' => ['jsonld' => 'application/ld+json']]);
    }

    public function testDocumentationActionV2(): void
    {
        $openApiFactoryProphecy = $this->prophesize(OpenApiFactoryInterface::class);
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getRequestFormat()->willReturn('json');
        $attributesProphecy = $this->prophesize(ParameterBagInterface::class);
        $queryProphecy = $this->prophesize(ParameterBag::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']));
        $requestProphecy->attributes = $attributesProphecy->reveal();
        $requestProphecy->query = $queryProphecy->reveal();
        $requestProphecy->getBaseUrl()->willReturn('/api')->shouldBeCalledTimes(1);
        $queryProphecy->getBoolean('api_gateway')->willReturn(true)->shouldBeCalledTimes(1);
        $queryProphecy->getInt('spec_version', 2)->willReturn(2)->shouldBeCalledTimes(1);
        $attributesProphecy->all()->willReturn(['_api_normalization_context' => ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => 2]])->shouldBeCalledTimes(1);
        $attributesProphecy->get('_api_normalization_context', [])->willReturn(['foo' => 'bar'])->shouldBeCalledTimes(1);
        $attributesProphecy->set('_api_normalization_context', ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => 2])->shouldBeCalledTimes(1);

        $documentation = new DocumentationAction($resourceNameCollectionFactoryProphecy->reveal(), 'My happy hippie api', 'lots of chocolate', '1.0.0', null, [2, 3], $openApiFactoryProphecy->reveal());
        $this->assertEquals(new Documentation(new ResourceNameCollection(['dummies']), 'My happy hippie api', 'lots of chocolate', '1.0.0'), $documentation($requestProphecy->reveal()));
    }

    public function testDocumentationActionV3(): void
    {
        $openApi = new OpenApi(new Info('my api', '1.0.0'), [], new Paths());
        $openApiFactoryProphecy = $this->prophesize(OpenApiFactoryInterface::class);
        $openApiFactoryProphecy->__invoke(Argument::any())->shouldBeCalled()->willReturn($openApi);
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getRequestFormat()->willReturn('json');
        $attributesProphecy = $this->prophesize(ParameterBagInterface::class);
        $queryProphecy = $this->prophesize(ParameterBag::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']));
        $requestProphecy->attributes = $attributesProphecy->reveal();
        $requestProphecy->query = $queryProphecy->reveal();
        $requestProphecy->getBaseUrl()->willReturn('/api')->shouldBeCalledTimes(1);
        $queryProphecy->getBoolean('api_gateway')->willReturn(true)->shouldBeCalledTimes(1);
        $queryProphecy->getInt('spec_version', 2)->willReturn(3)->shouldBeCalledTimes(1);
        $attributesProphecy->all()->willReturn(['_api_normalization_context' => ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => 3]])->shouldBeCalledTimes(1);
        $attributesProphecy->get('_api_normalization_context', [])->willReturn(['foo' => 'bar'])->shouldBeCalledTimes(1);
        $attributesProphecy->set('_api_normalization_context', ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => 3])->shouldBeCalledTimes(1);

        $documentation = new DocumentationAction($resourceNameCollectionFactoryProphecy->reveal(), 'My happy hippie api', 'lots of chocolate', '1.0.0', null, [2, 3], $openApiFactoryProphecy->reveal());
        $this->assertInstanceOf(OpenApi::class, $documentation($requestProphecy->reveal()));
    }
}
