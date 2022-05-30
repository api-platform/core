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
use ApiPlatform\Tests\ProphecyTrait;
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

    public function testDocumentationAction(): void
    {
        $openApi = new OpenApi(new Info('my api', '1.0.0'), [], new Paths());
        $openApiFactoryProphecy = $this->prophesize(OpenApiFactoryInterface::class);
        $openApiFactoryProphecy->__invoke(Argument::any())->shouldBeCalled()->willReturn($openApi);
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getRequestFormat()->willReturn('json');
        $attributesProphecy = $this->prophesize(ParameterBagInterface::class);
        $queryProphecy = $this->prophesize(ParameterBag::class);
        $requestProphecy->attributes = $attributesProphecy->reveal();
        $requestProphecy->query = $queryProphecy->reveal();
        $requestProphecy->getBaseUrl()->willReturn('/api')->shouldBeCalledTimes(1);
        $queryProphecy->getBoolean('api_gateway')->willReturn(true)->shouldBeCalledTimes(1);
        $queryProphecy->getInt('spec_version', 2)->willReturn(3)->shouldBeCalledTimes(1);
        $attributesProphecy->all()->willReturn(['_api_normalization_context' => ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => 3]])->shouldBeCalledTimes(1);
        $attributesProphecy->get('_api_normalization_context', [])->willReturn(['foo' => 'bar'])->shouldBeCalledTimes(1);
        $attributesProphecy->set('_api_normalization_context', ['foo' => 'bar', 'base_url' => '/api', 'api_gateway' => true, 'spec_version' => 3])->shouldBeCalledTimes(1);

        $documentation = new DocumentationAction($openApiFactoryProphecy->reveal());
        $this->assertInstanceOf(OpenApi::class, $documentation($requestProphecy->reveal()));
    }
}
