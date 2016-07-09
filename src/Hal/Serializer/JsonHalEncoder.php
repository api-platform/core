<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hal\Serializer;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * JSON-LD Encoder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class JsonHalEncoder implements EncoderInterface, DecoderInterface
{
    const FORMAT = 'jsonhal';

    private $jsonEncoder;

    public function __construct(JsonEncoder $jsonEncoder = null)
    {
        // Encode <, >, ', &, and " for RFC4627-compliant JSON, which may also be embedded into HTML.
        $this->jsonEncoder = $jsonEncoder ?: new JsonEncoder(
            new JsonEncode(JsonResponse::DEFAULT_ENCODING_OPTIONS), new JsonDecode(true)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format)
    {
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = [])
    {
        return $this->jsonEncoder->encode($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = [])
    {
        return $this->jsonEncoder->decode($data, $format, $context);
    }
}
