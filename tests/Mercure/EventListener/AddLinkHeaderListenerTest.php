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
use ApiPlatform\Core\Tests\ProphecyTrait;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\WebLink\HttpHeaderSerializer;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AddLinkHeaderListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider addProvider
     */
    public function testAddLinkHeader(string $expected, Request $request)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => true]));

        $event = new ResponseEvent(
          $this->prophesize(HttpKernelInterface::class)->reveal(),
          $request,
          HttpKernelInterface::MASTER_REQUEST,
          new Response()
        );

        $listener = new AddLinkHeaderListener($resourceMetadataFactoryProphecy->reveal(), 'https://demo.mercure.rocks/hub');
        $listener->onKernelResponse($event);

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

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $listener = new AddLinkHeaderListener($resourceMetadataFactoryProphecy->reveal(), 'https://demo.mercure.rocks/hub');
        $listener->onKernelResponse($event);

        $this->assertNull($request->attributes->get('_links'));
    }

    public function doNotAddProvider(): array
    {
        return [
            [new Request()],
            [new Request([], [], ['_api_resource_class' => Dummy::class])],
        ];
    }

    public function testSkipWhenPreflightRequest(): void
    {
        $request = new Request();
        $request->setMethod('OPTIONS');
        $request->headers->set('Access-Control-Request-Method', 'POST');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $listener = new AddLinkHeaderListener($resourceMetadataFactory->reveal(), 'http://example.com/.well-known/mercure');
        $listener->onKernelResponse($event);

        $this->assertFalse($request->attributes->has('_links'));
    }
}
