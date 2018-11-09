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

namespace ApiPlatform\Core\Tests\Hydra\EventListener;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Hydra\EventListener\AddLinkHeaderListener;
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
     * @dataProvider provider
     */
    public function testAddLinkHeader(string $expected, Request $request)
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('http://example.com/docs')->shouldBeCalled();

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new AddLinkHeaderListener($urlGenerator->reveal());
        $listener->onKernelResponse($event->reveal());
        $this->assertSame($expected, (new HttpHeaderSerializer())->serialize($request->attributes->get('_links')->getLinks()));
    }

    public function provider(): array
    {
        return [
            ['<http://example.com/docs>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"', new Request()],
            ['<https://demo.mercure.rocks/hub>; rel="mercure",<http://example.com/docs>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"', new Request([], [], ['_links' => new GenericLinkProvider([new Link('mercure', 'https://demo.mercure.rocks/hub')])])],
        ];
    }
}
