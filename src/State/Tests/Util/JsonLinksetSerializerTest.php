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
use ApiPlatform\State\Util\JsonLinksetSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\WebLink\Link;

/**
 * @author Florent Morselli <florent.morselli@spomky-labs.com>
 */
class JsonLinksetSerializerTest extends TestCase
{
    private JsonLinksetSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new JsonLinksetSerializer();
    }

    public function testSerializeEmpty(): void
    {
        $this->assertSame('{"linkset":[]}', $this->serializer->serialize([]));
    }

    public function testSerializeSingleLink(): void
    {
        $links = [
            (new Link('next', 'https://example.com/foo'))->withAttribute('anchor', 'https://example.net/bar'),
        ];

        $this->assertJsonStringEqualsJsonString(<<<'JSON'
            {"linkset": [
                {"anchor": "https://example.net/bar", "next": [{"href": "https://example.com/foo"}]}
            ]}
            JSON, $this->serializer->serialize($links));
    }

    public function testSerializeGroupsLinksSharingTheSameContextAndRelation(): void
    {
        $links = [
            (new Link('item', 'https://example.com/foo1'))->withAttribute('anchor', 'https://example.net/bar'),
            (new Link('item', 'https://example.com/foo2'))->withAttribute('anchor', 'https://example.net/bar'),
        ];

        $this->assertJsonStringEqualsJsonString(<<<'JSON'
            {"linkset": [
                {"anchor": "https://example.net/bar", "item": [
                    {"href": "https://example.com/foo1"},
                    {"href": "https://example.com/foo2"}
                ]}
            ]}
            JSON, $this->serializer->serialize($links));
    }

    public function testSerializeSplitsDistinctContexts(): void
    {
        $links = [
            (new Link('next', 'https://example.com/foo1'))->withAttribute('anchor', 'https://example.net/bar'),
            (new Link('https://example.com/relations/baz', 'https://example.com/foo2'))->withAttribute('anchor', 'https://example.net/boo'),
        ];

        $this->assertJsonStringEqualsJsonString(<<<'JSON'
            {"linkset": [
                {"anchor": "https://example.net/bar", "next": [{"href": "https://example.com/foo1"}]},
                {"anchor": "https://example.net/boo", "https://example.com/relations/baz": [{"href": "https://example.com/foo2"}]}
            ]}
            JSON, $this->serializer->serialize($links));
    }

    public function testSerializeWithoutAnchorOmitsTheContext(): void
    {
        $this->assertJsonStringEqualsJsonString(<<<'JSON'
            {"linkset": [
                {"next": [{"href": "https://example.com/foo"}]}
            ]}
            JSON, $this->serializer->serialize([new Link('next', 'https://example.com/foo')]));
    }

    public function testSerializeDistinguishesAnEmptyAnchorFromNoAnchor(): void
    {
        $links = [
            (new Link('next', 'https://example.com/foo'))->withAttribute('anchor', ''),
            new Link('next', 'https://example.com/bar'),
        ];

        $this->assertJsonStringEqualsJsonString(<<<'JSON'
            {"linkset": [
                {"anchor": "", "next": [{"href": "https://example.com/foo"}]},
                {"next": [{"href": "https://example.com/bar"}]}
            ]}
            JSON, $this->serializer->serialize($links));
    }

    public function testSerializeNumericAnchor(): void
    {
        $links = [(new Link('next', 'https://example.com/foo'))->withAttribute('anchor', '123')];

        $this->assertJsonStringEqualsJsonString('{"linkset":[{"anchor":"123","next":[{"href":"https://example.com/foo"}]}]}', $this->serializer->serialize($links));
    }

    public function testSerializeRepeatsMultipleRelations(): void
    {
        $links = [(new Link('alternate', 'https://example.com/foo'))->withRel('next')];

        $this->assertJsonStringEqualsJsonString(<<<'JSON'
            {"linkset": [
                {
                    "alternate": [{"href": "https://example.com/foo"}],
                    "next": [{"href": "https://example.com/foo"}]
                }
            ]}
            JSON, $this->serializer->serialize($links));
    }

    public function testSerializeTargetAttributesDefinedByWebLinking(): void
    {
        $links = [
            (new Link('next', 'https://example.com/foo'))
                ->withAttribute('anchor', 'https://example.net/bar')
                ->withAttribute('type', 'text/html')
                ->withAttribute('hreflang', ['en', 'de'])
                ->withAttribute('media', 'screen'),
        ];

        $this->assertJsonStringEqualsJsonString(<<<'JSON'
            {"linkset": [
                {"anchor": "https://example.net/bar", "next": [{
                    "href": "https://example.com/foo",
                    "type": "text/html",
                    "hreflang": ["en", "de"],
                    "media": "screen"
                }]}
            ]}
            JSON, $this->serializer->serialize($links));
    }

    public function testSerializeWrapsSingleValuedHreflangInAnArray(): void
    {
        $links = [(new Link('next', 'https://example.com/foo'))->withAttribute('hreflang', 'en')];

        $this->assertJsonStringEqualsJsonString('{"linkset":[{"next":[{"href":"https://example.com/foo","hreflang":["en"]}]}]}', $this->serializer->serialize($links));
    }

    public function testSerializeKeepsOnlyTheFirstValueOfNonRepeatableAttributes(): void
    {
        $links = [(new Link('next', 'https://example.com/foo'))->withAttribute('type', ['text/html', 'text/plain'])];

        $this->assertJsonStringEqualsJsonString('{"linkset":[{"next":[{"href":"https://example.com/foo","type":"text/html"}]}]}', $this->serializer->serialize($links));
    }

    public function testSerializeInternationalizedTargetAttributes(): void
    {
        $links = [
            (new Link('next', 'https://example.com/foo'))
                ->withAttribute('anchor', 'https://example.net/bar')
                ->withAttribute('title', 'Next chapter')
                ->withAttribute('title*', "UTF-8'de'n%c3%a4chstes%20Kapitel"),
        ];

        $this->assertJsonStringEqualsJsonString(<<<'JSON'
            {"linkset": [
                {"anchor": "https://example.net/bar", "next": [{
                    "href": "https://example.com/foo",
                    "title": "Next chapter",
                    "title*": [{"value": "nächstes Kapitel", "language": "de"}]
                }]}
            ]}
            JSON, $this->serializer->serialize($links));
    }

    public function testSerializeInternationalizedTargetAttributeWithoutLanguage(): void
    {
        $links = [(new Link('next', 'https://example.com/foo'))->withAttribute('title*', "UTF-8''Next%20chapter")];

        $this->assertJsonStringEqualsJsonString('{"linkset":[{"next":[{"href":"https://example.com/foo","title*":[{"value":"Next chapter"}]}]}]}', $this->serializer->serialize($links));
    }

    public function testSerializeExtensionTargetAttributesAsArrays(): void
    {
        $links = [
            (new Link('next', 'https://example.com/foo'))
                ->withAttribute('anchor', 'https://example.net/bar')
                ->withAttribute('type', 'text/html')
                ->withAttribute('foo', 'foovalue')
                ->withAttribute('bar', ['barone', 'bartwo'])
                ->withAttribute('baz*', "UTF-8'en'bazvalue"),
        ];

        $this->assertJsonStringEqualsJsonString(<<<'JSON'
            {"linkset": [
                {"anchor": "https://example.net/bar", "next": [{
                    "href": "https://example.com/foo",
                    "type": "text/html",
                    "foo": ["foovalue"],
                    "bar": ["barone", "bartwo"],
                    "baz*": [{"value": "bazvalue", "language": "en"}]
                }]}
            ]}
            JSON, $this->serializer->serialize($links));
    }

    public function testSerializeBooleanAttributes(): void
    {
        $links = [
            (new Link('preload', 'https://example.com/foo'))
                ->withAttribute('nopush', true)
                ->withAttribute('nofollow', false),
        ];

        $this->assertJsonStringEqualsJsonString('{"linkset":[{"preload":[{"href":"https://example.com/foo","nopush":[""]}]}]}', $this->serializer->serialize($links));
    }

    public function testSerializeSkipsTemplatedLinks(): void
    {
        $links = [
            new Link('item', 'https://example.com/users/{id}'),
            new Link('next', 'https://example.com/foo'),
        ];

        $this->assertJsonStringEqualsJsonString('{"linkset":[{"next":[{"href":"https://example.com/foo"}]}]}', $this->serializer->serialize($links));
    }

    public function testSerializeSkipsLinksWithoutARelationType(): void
    {
        $links = [
            new Link(null, 'https://example.com/foo'),
            new Link('next', 'https://example.com/bar'),
        ];

        $this->assertJsonStringEqualsJsonString('{"linkset":[{"next":[{"href":"https://example.com/bar"}]}]}', $this->serializer->serialize($links));
    }

    public function testSerializeTreatsAFalseAnchorAsAbsent(): void
    {
        $links = [(new Link('next', 'https://example.com/foo'))->withAttribute('anchor', false)];

        $this->assertJsonStringEqualsJsonString('{"linkset":[{"next":[{"href":"https://example.com/foo"}]}]}', $this->serializer->serialize($links));
    }

    public function testSerializeThrowsOnTheAnchorRelationType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A link with the "anchor" relation type cannot be represented in an "application/linkset+json" document.');

        $this->serializer->serialize([(new Link('anchor', 'https://example.com/foo'))->withAttribute('anchor', 'https://example.net/bar')]);
    }

    public function testSerializeThrowsWhenTheLinksCannotBeEncoded(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The link set cannot be serialized to JSON: "Malformed UTF-8 characters, possibly incorrectly encoded".');

        $this->serializer->serialize([(new Link('next', '/foo'))->withAttribute('title', "\xB1\x31")]);
    }

    public function testSerializeThrowsWhenTheLinksCannotBeEncodedWhateverTheFlags(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->serializer->serialize([(new Link('next', '/foo'))->withAttribute('title', "\xB1\x31")], \JSON_THROW_ON_ERROR);
    }

    public function testSerializeForwardsJsonEncodeFlags(): void
    {
        $links = [new Link('next', 'https://example.com/foo')];

        $this->assertStringContainsString('https://example.com/foo', $this->serializer->serialize($links, \JSON_UNESCAPED_SLASHES));
        $this->assertStringContainsString('https:\/\/example.com\/foo', $this->serializer->serialize($links));
    }
}
