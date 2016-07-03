<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\JsonLd\Serializer;

use ApiPlatform\Core\JsonLd\Serializer\JsonLdEncoder;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class JsonLdEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonLdEncoder
     */
    private $encoder;

    public function setUp()
    {
        $this->encoder = new JsonLdEncoder();
    }

    public function testSupportEncoding()
    {
        $this->assertTrue($this->encoder->supportsEncoding(JsonLdEncoder::FORMAT));
        $this->assertFalse($this->encoder->supportsEncoding('csv'));
    }

    public function testEncode()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals('{"foo":"bar"}', $this->encoder->encode($data, JsonLdEncoder::FORMAT));
    }

    public function testSupportDecoding()
    {
        $this->assertTrue($this->encoder->supportsDecoding(JsonLdEncoder::FORMAT));
        $this->assertFalse($this->encoder->supportsDecoding('csv'));
    }

    public function testDecode()
    {
        $this->assertEquals(['foo' => 'bar'], $this->encoder->decode('{"foo":"bar"}', JsonLdEncoder::FORMAT));
    }
}
