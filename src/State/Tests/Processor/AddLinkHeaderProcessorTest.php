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

namespace ApiPlatform\State\Tests\Processor;

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\Processor\AddLinkHeaderProcessor;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

class AddLinkHeaderProcessorTest extends TestCase
{
    public function testWithoutLinks(): void
    {
        $data = new \stdClass();
        $operation = new Get();
        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($data);
        $processor = new AddLinkHeaderProcessor($decorated);
        $this->assertEquals($data, $processor->process($data, $operation));
    }

    public function testAddsTheLinkAndLinkTemplateHeaders(): void
    {
        $response = $this->process([
            new Link('http://www.w3.org/ns/hydra/core#apiDocumentation', '/docs.jsonld'),
            (new Link('author', '/books/{book_id}/author'))->withAttribute('anchor', '#{book_id}'),
        ]);

        $this->assertSame('</docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"', $response->headers->get('Link'));
        $this->assertSame('"/books/{book_id}/author"; rel="author"; anchor="#{book_id}"', $response->headers->get('Link-Template'));
    }

    public function testDoesNotAddALinkHeaderWhenEveryLinkIsTemplated(): void
    {
        $response = $this->process([new Link('item', '/books/{id}')]);

        $this->assertFalse($response->headers->has('Link'));
        $this->assertSame('"/books/{id}"; rel="item"', $response->headers->get('Link-Template'));
    }

    public function testDoesNotAddALinkTemplateHeaderWithoutTemplatedLinks(): void
    {
        $response = $this->process([new Link('preload', '/style.css')]);

        $this->assertSame('</style.css>; rel="preload"', $response->headers->get('Link'));
        $this->assertFalse($response->headers->has('Link-Template'));
    }

    /**
     * @param Link[] $links
     */
    private function process(array $links): Response
    {
        $request = new Request();
        $request->attributes->set('_api_platform_links', new GenericLinkProvider($links));

        $response = new Response();
        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($response);

        $processor = new AddLinkHeaderProcessor($decorated);

        return $processor->process(new \stdClass(), new Get(), [], ['request' => $request]);
    }
}
