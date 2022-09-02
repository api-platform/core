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

namespace ApiPlatform\Tests\Serializer;

use ApiPlatform\Serializer\JsonEncoder;
use PHPUnit\Framework\TestCase;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class JsonEncoderTest extends TestCase
{
    /**
     * @var JsonEncoder
     */
    private $encoder;

    protected function setUp(): void
    {
        $this->encoder = new JsonEncoder('json');
    }

    public function testSupportEncoding()
    {
        $this->assertTrue($this->encoder->supportsEncoding('json'));
        $this->assertFalse($this->encoder->supportsEncoding('csv'));
    }

    public function testEncode()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals('{"foo":"bar"}', $this->encoder->encode($data, 'json'));
    }

    public function testSupportDecoding()
    {
        $this->assertTrue($this->encoder->supportsDecoding('json'));
        $this->assertFalse($this->encoder->supportsDecoding('csv'));
    }

    public function testDecode()
    {
        $this->assertEquals(['foo' => 'bar'], $this->encoder->decode('{"foo":"bar"}', 'json'));
    }

    public function testUTF8EncodedString()
    {
        $data = ['foo' => 'Über'];

        $this->assertEquals('{"foo":"Über"}', $this->encoder->encode($data, 'json'));
    }

    public function testUTF8MalformedHandlingEncoding()
    {
        if (\PHP_VERSION_ID >= 70200) {
            $data = ['foo' => pack('H*', 'B11111')];

            $this->assertEquals('{"foo":"\u0011\u0011"}', $this->encoder->encode($data, 'json'));
        }
    }
}
