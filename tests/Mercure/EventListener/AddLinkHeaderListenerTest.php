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

namespace ApiPlatform\Core\Tests\Mercure\EventListener;

use ApiPlatform\Core\Mercure\EventListener\AddLinkHeaderListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\WebLink\HttpHeaderSerializer;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddLinkHeaderListenerTest extends TestCase
{
    /**
     * @dataProvider addProvider
     */
    public function testAddLinkHeader(string $expected, Request $request)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => true]));

        $listener = new AddLinkHeaderListener($resourceMetadataFactoryProphecy->reveal(), 'https://demo.mercure.rocks/hub');

        $eventProphecy = $this->prophesize(FilterResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $listener->onKernelResponse($eventProphecy->reveal());

        $this->assertSame($expected, (new HttpHeaderSerializer())->serialize($request->attributes->get('_links')->getLinks()));
    }

    public function addProvider(): array
    {
        return [
            ['<https://demo.mercure.rocks/hub>; rel="mercure"', new Request([], [], ['_api_resource_class' => Dummy::class])],
            ['<http://example.com/docs>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation",<https://demo.mercure.rocks/hub>; rel="mercure"', new Request([], [], ['_api_resource_class' => Dummy::class, '_links' => new GenericLinkProvider([new Link('http://www.w3.org/ns/hydra/core#apiDocumentation', 'http://example.com/docs')])])],
        ];
    }

    /**
     * @dataProvider doNotAddProvider
     */
    public function testDoNotAddHeader(Request $request)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $listener = new AddLinkHeaderListener($resourceMetadataFactoryProphecy->reveal(), 'https://demo.mercure.rocks/hub');

        $eventProphecy = $this->prophesize(FilterResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $listener->onKernelResponse($eventProphecy->reveal());

        $this->assertNull($request->attributes->get('_links'));
    }

    public function doNotAddProvider(): array
    {
        return [
            [new Request()],
            [new Request([], [], ['_api_resource_class' => Dummy::class])],
        ];
    }
}
