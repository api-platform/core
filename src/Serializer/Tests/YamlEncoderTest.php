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

        $this->assertSame('{ foo: bar }', $this->encoder->encode($data, 'yamlopenapi'));
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

        $this->assertEquals('{ foo: Über }', $this->encoder->encode($data, 'yamlopenapi'));
    }
}
