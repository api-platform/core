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

namespace ApiPlatform\Serializer\Tests;

use ApiPlatform\Serializer\YamlEncoder;
use PHPUnit\Framework\TestCase;

class YamlEncoderTest extends TestCase
{
    private YamlEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new YamlEncoder('yamlopenapi');
    }

    public function testSupportEncoding(): void
    {
        $this->assertTrue($this->encoder->supportsEncoding('yamlopenapi'));
        $this->assertFalse($this->encoder->supportsEncoding('json'));
    }

    public function testEncode(): void
    {
        $data = ['foo' => 'bar'];

        $this->assertSame("foo: bar\n", $this->encoder->encode($data, 'yamlopenapi'));
    }

    public function testEncodeUsesOpenApiYamlFlags(): void
    {
        $data = [
            'openapi' => '3.1.0',
            'paths' => [
                '/foo' => [
                    'get' => [
                        'summary' => 'list',
                        'tags' => [],
                        'responses' => [
                            '200' => ['description' => "Multi\nline\ndescription"],
                        ],
                    ],
                ],
            ],
        ];

        $encoded = $this->encoder->encode($data, 'yamlopenapi');

        // Not inline: the CLI export uses Yaml::dump with inline=10 + flags.
        $this->assertStringContainsString("paths:\n", $encoded);
        $this->assertStringContainsString("    get:\n", $encoded);
        // DUMP_EMPTY_ARRAY_AS_SEQUENCE → empty tags is "tags: []", not "tags: {  }".
        $this->assertStringContainsString('tags: []', $encoded);
        // DUMP_NUMERIC_KEY_AS_STRING → "200" keeps its quotes.
        $this->assertStringContainsString("'200':", $encoded);
        // DUMP_MULTI_LINE_LITERAL_BLOCK → literal block scalar for multiline strings.
        $this->assertStringContainsString("description: |-\n", $encoded);
    }

    public function testSupportDecoding(): void
    {
        $this->assertTrue($this->encoder->supportsDecoding('yamlopenapi'));
        $this->assertFalse($this->encoder->supportsDecoding('json'));
    }

    public function testDecode(): void
    {
        $this->assertEquals(['foo' => 'bar'], $this->encoder->decode('{ foo: bar }', 'yamlopenapi'));
    }

    public function testUTF8EncodedString(): void
    {
        $data = ['foo' => 'Über'];

        $this->assertEquals("foo: Über\n", $this->encoder->encode($data, 'yamlopenapi'));
    }
}
