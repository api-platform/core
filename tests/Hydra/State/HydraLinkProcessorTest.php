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

namespace ApiPlatform\Tests\Hydra\State;

use ApiPlatform\Hydra\State\HydraLinkProcessor;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

class HydraLinkProcessorTest extends TestCase
{
    public function testProcess(): void
    {
        $data = new \stdClass();
        $operation = new Get(links: [new Link('a', 'b')]);
        $request = $this->createMock(Request::class);
        $request->attributes = $this->createMock(ParameterBag::class);

        $request->attributes->expects($this->once())->method('set')->with('_links', $this->callback(function ($linkProvider) {
            $this->assertInstanceOf(GenericLinkProvider::class, $linkProvider);
            $this->assertEquals($linkProvider->getLinks(), [new Link('a', 'b'), new Link(ContextBuilder::HYDRA_NS.'apiDocumentation', '/docs')]);

            return true;
        }));
        $context = ['request' => $request];
        $decorated = $this->createMock(ProcessorInterface::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())->method('generate')->with('api_doc', ['_format' => 'jsonld'], UrlGeneratorInterface::ABS_URL)->willReturn('/docs');
        (new HydraLinkProcessor($decorated, $urlGenerator))->process($data, $operation, [], $context);
    }
}
