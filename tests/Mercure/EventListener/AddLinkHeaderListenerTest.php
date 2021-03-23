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

    private $defaultHub;
    private $managedHub;
    private $mercure;
    private $discovery;

    protected function setUp(): void
    {
        $this->defaultHub = new Hub('https://internal/.well-known/mercure', new StaticTokenProvider('xxx'), null, 'https://external/.well-known/mercure');
        $this->managedHub = new Hub('https://managed.mercure.rocks/.well-known/mercure', new StaticTokenProvider('xxx'), null, 'https://managed.mercure.rocks/.well-known/mercure');

        $this->mercure = new HubRegistry($this->defaultHub, ['default' => $this->defaultHub, 'managed' => $this->managedHub]);
        $this->discovery = new Discovery($this->mercure);
    }

    /**
     * @dataProvider addProvider
     */
    public function testAddLinkHeader(string $expected, Request $request)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => ['hub' => 'managed']]));

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $listener = new AddLinkHeaderListener($resourceMetadataFactoryProphecy->reveal(), $this->discovery);
        $listener->onKernelResponse($event);

        $this->assertSame($expected, (new HttpHeaderSerializer())->serialize($request->attributes->get('_links')->getLinks()));
    }

    /**
     * @dataProvider addProvider
     * @group legacy
     */
    public function testAddLinkHeaderWithLegacySignature(string $expected, Request $request)
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['mercure' => true]));

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $listener = new AddLinkHeaderListener($resourceMetadataFactoryProphecy->reveal(), $this->managedHub->getPublicUrl());
        $listener->onKernelResponse($event);

        $this->assertSame($expected, (new HttpHeaderSerializer())->serialize($request->attributes->get('_links')->getLinks()));
    }

    public function addProvider(): array
    {
        return [
            ['<https://managed.mercure.rocks/.well-known/mercure>; rel="mercure"', new Request([], [], ['_api_resource_class' => Dummy::class])],
            ['<http://example.com/docs>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation",<https://managed.mercure.rocks/.well-known/mercure>; rel="mercure"', new Request([], [], ['_api_resource_class' => Dummy::class, '_links' => new GenericLinkProvider([new Link('http://www.w3.org/ns/hydra/core#apiDocumentation', 'http://example.com/docs')])])],
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

        $listener = new AddLinkHeaderListener($resourceMetadataFactoryProphecy->reveal(), $this->discovery);
        $listener->onKernelResponse($event);

        $this->assertNull($request->attributes->get('_links'));
    }

    /**
     * @dataProvider doNotAddProvider
     * @group legacy
     */
    public function testDoNotAddHeaderWithLegacySignature(Request $request)
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

        $listener = new AddLinkHeaderListener($resourceMetadataFactory->reveal(), $this->discovery);
        $listener->onKernelResponse($event);

        $this->assertFalse($request->attributes->has('_links'));
    }

    /**
     * @group legacy
     */
    public function testSkipWhenPreflightRequestWithLegacySignature(): void
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
