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

namespace ApiPlatform\Tests\Symfony\EventListener;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\EventListener\AddLinkHeaderListener;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(operations: [
                'get' => new Get(mercure: [
                    'hub' => 'managed',
                ]),
            ])),
        ]));

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $defaultHub = new Hub('https://internal/.well-known/mercure', new StaticTokenProvider('xxx'), null, 'https://external/.well-known/mercure');
        $managedHub = new Hub('https://managed.mercure.rocks/.well-known/mercure', new StaticTokenProvider('xxx'), null, 'https://managed.mercure.rocks/.well-known/mercure');

        $mercure = new HubRegistry($defaultHub, ['default' => $defaultHub, 'managed' => $managedHub]);
        $discovery = new Discovery($mercure);

        $listener = new AddLinkHeaderListener($discovery, $resourceMetadataFactoryProphecy->reveal());
        $listener->onKernelResponse($event);
        $this->assertSame($expected, (new HttpHeaderSerializer())->serialize($request->attributes->get('_links')->getLinks()));
    }

    public function addProvider(): array
    {
        return [
            ['<https://managed.mercure.rocks/.well-known/mercure>; rel="mercure"', new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get'])],
            ['<http://example.com/docs>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation",<https://managed.mercure.rocks/.well-known/mercure>; rel="mercure"', new Request([], [], ['_api_resource_class' => Dummy::class, '_api_operation_name' => 'get', '_links' => new GenericLinkProvider([new Link('http://www.w3.org/ns/hydra/core#apiDocumentation', 'http://example.com/docs')])])],
        ];
    }

    /**
     * @dataProvider doNotAddProvider
     */
    public function testDoNotAddHeader(Request $request)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource(operations: [new Get()])),
        ]));

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $defaultHub = new Hub('https://internal/.well-known/mercure', new StaticTokenProvider('xxx'), null, 'https://external/.well-known/mercure');
        $registry = new HubRegistry($defaultHub, ['default' => $defaultHub]);
        $listener = new AddLinkHeaderListener(new Discovery($registry), $resourceMetadataFactoryProphecy->reveal());
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
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $defaultHub = new Hub('https://internal/.well-known/mercure', new StaticTokenProvider('xxx'), null, 'https://external/.well-known/mercure');
        $registry = new HubRegistry($defaultHub, ['default' => $defaultHub]);
        $listener = new AddLinkHeaderListener(new Discovery($registry));
        $listener->onKernelResponse($event);

        $this->assertFalse($request->attributes->has('_links'));
    }
}
