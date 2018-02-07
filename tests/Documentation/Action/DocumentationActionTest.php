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

use ApiPlatform\Core\Documentation\Action\DocumentationAction;
use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class DocumentationActionTest extends TestCase
{
    public function testDocumentationAction()
    {
        $requestProphecy = $this->prophesize(Request::class);
        $attributesProphecy = $this->prophesize(ParameterBagInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection(['dummies']));
        $requestProphecy->attributes = $attributesProphecy->reveal();
        $requestProphecy->getBaseUrl()->willReturn('/api')->shouldBeCalledTimes(1);
        $attributesProphecy->get('_api_normalization_context', [])->willReturn(['foo' => 'bar'])->shouldBeCalledTimes(1);
        $attributesProphecy->set('_api_normalization_context', ['foo' => 'bar', 'base_url' => '/api'])->shouldBeCalledTimes(1);
        $documentation = new DocumentationAction($resourceNameCollectionFactoryProphecy->reveal(), 'My happy hippie api', 'lots of chocolate', '1.0.0', ['formats' => ['jsonld' => 'application/ld+json']]);
        $this->assertEquals(new Documentation(new ResourceNameCollection(['dummies']), 'My happy hippie api', 'lots of chocolate', '1.0.0', ['formats' => ['jsonld' => 'application/ld+json']]), $documentation($requestProphecy->reveal()));
    }
}
