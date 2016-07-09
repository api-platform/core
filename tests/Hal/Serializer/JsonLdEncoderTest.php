<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Hal\Serializer;

use ApiPlatform\Core\Hal\Serializer\JsonHalEncoder;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class JsonLdEncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonHalEncoder
     */
    private $encoder;

    public function setUp()
    {
        $this->encoder = new JsonHalEncoder();
    }

    public function testSupportEncoding()
    {
        $this->assertTrue($this->encoder->supportsEncoding(JsonHalEncoder::FORMAT));
        $this->assertFalse($this->encoder->supportsEncoding('csv'));
    }

    public function testEncode()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals('{"foo":"bar"}', $this->encoder->encode($data, JsonHalEncoder::FORMAT));
    }

    public function testSupportDecoding()
    {
        $this->assertTrue($this->encoder->supportsDecoding(JsonHalEncoder::FORMAT));
        $this->assertFalse($this->encoder->supportsDecoding('csv'));
    }

    public function testDecode()
    {
        $this->assertEquals(['foo' => 'bar'], $this->encoder->decode('{"foo":"bar"}', JsonHalEncoder::FORMAT));
    }
}
