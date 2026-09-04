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

namespace ApiPlatform\State\Tests\Util;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\State\Util\LinkTemplateHeaderSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\WebLink\Link;

/**
 * @author Florent Morselli <florent.morselli@spomky-labs.com>
 */
class LinkTemplateHeaderSerializerTest extends TestCase
{
    private LinkTemplateHeaderSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new LinkTemplateHeaderSerializer();
    }

    public function testSerializeEmpty(): void
    {
        $this->assertNull($this->serializer->serialize([]));
    }

    public function testSerialize(): void
    {
        $this->assertSame('"/{username}"; rel="item"', $this->serializer->serialize([new Link('item', '/{username}')]));
    }

    public function testSerializeSeveralTemplates(): void
    {
        $links = [
            new Link('item', '/{username}'),
            (new Link('alternate', '/{username}{?format}'))->withRel('next'),
        ];

        $this->assertSame('"/{username}"; rel="item", "/{username}{?format}"; rel="alternate next"', $this->serializer->serialize($links));
    }

    public function testSerializeTemplatedAnchor(): void
    {
        $links = [(new Link('author', '/books/{book_id}/author'))->withAttribute('anchor', '#{book_id}')];

        $this->assertSame('"/books/{book_id}/author"; rel="author"; anchor="#{book_id}"', $this->serializer->serialize($links));
    }

    public function testSerializeVarBase(): void
    {
        $links = [
            (new Link('https://example.org/rel/widget', '/widgets/{widget_id}'))
                ->withAttribute('var-base', 'https://example.org/vars/'),
        ];

        $this->assertSame('"/widgets/{widget_id}"; rel="https://example.org/rel/widget"; var-base="https://example.org/vars/"', $this->serializer->serialize($links));
    }

    public function testSerializeNonAsciiAttributeAsADisplayString(): void
    {
        $links = [(new Link('author', '/authors/{id}'))->withAttribute('title', 'Björn Järnsida')];

        $this->assertSame('"/authors/{id}"; rel="author"; title=%"Bj%c3%b6rn J%c3%a4rnsida"', $this->serializer->serialize($links));
    }

    public function testSerializeEscapesStrings(): void
    {
        $links = [(new Link('item', '/{id}'))->withAttribute('title', 'a "quoted" \\ value')];

        $this->assertSame('"/{id}"; rel="item"; title="a \"quoted\" \\\\ value"', $this->serializer->serialize($links));
    }

    public function testSerializeNonPrintableAttributeAsADisplayString(): void
    {
        $links = [(new Link('item', '/{id}'))->withAttribute('title', "Hello\n")];

        $this->assertSame('"/{id}"; rel="item"; title=%"Hello%0a"', $this->serializer->serialize($links));
    }

    public function testSerializeBooleanAttributes(): void
    {
        $links = [
            (new Link('item', '/{id}'))
                ->withAttribute('nopush', true)
                ->withAttribute('nofollow', false),
        ];

        $this->assertSame('"/{id}"; rel="item"; nopush', $this->serializer->serialize($links));
    }

    public function testSerializeRepeatedAttributes(): void
    {
        $links = [(new Link('item', '/{id}'))->withAttribute('hreflang', ['fr', 'de'])];

        $this->assertSame('"/{id}"; rel="item"; hreflang="fr"; hreflang="de"', $this->serializer->serialize($links));
    }

    public function testSerializeLowercasesAttributeNames(): void
    {
        $links = [(new Link('item', '/{id}'))->withAttribute('Title', 'Hello')];

        $this->assertSame('"/{id}"; rel="item"; title="Hello"', $this->serializer->serialize($links));
    }

    public function testSerializeWithoutRel(): void
    {
        $this->assertSame('"/{id}"', $this->serializer->serialize([new Link(null, '/{id}')]));
    }

    public function testSerializeSkipsLinksThatAreNotTemplated(): void
    {
        $links = [
            new Link('preload', '/style.css'),
            new Link('item', '/{id}'),
        ];

        $this->assertSame('"/{id}"; rel="item"', $this->serializer->serialize($links));
    }

    public function testSerializeThrowsOnAnInvalidParameterKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "0invalid" target attribute cannot be serialized as a structured field parameter key.');

        $this->serializer->serialize([(new Link('item', '/{id}'))->withAttribute('0invalid', 'value')]);
    }

    public function testSerializeThrowsOnANonUtf8Value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Non-ASCII strings must be encoded in UTF-8 to be serialized as structured field display strings.');

        $this->serializer->serialize([(new Link('item', '/{id}'))->withAttribute('title', "Bj\xF6rn")]);
    }
}
