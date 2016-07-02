<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\JsonLd\Serializer;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * JSON-LD Encoder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class JsonLdEncoder extends JsonEncoder
{
    const FORMAT = 'jsonld';

    public function __construct(JsonEncode $encodingImpl = null, JsonDecode $decodingImpl = null)
    {
        // Encode <, >, ', &, and " for RFC4627-compliant JSON, which may also be embedded into HTML.
        $this->encodingImpl = $encodingImpl ?: new JsonEncode(JsonResponse::DEFAULT_ENCODING_OPTIONS);
        $this->decodingImpl = $decodingImpl ?: new JsonDecode(true);
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
    public function supportsDecoding($format)
    {
        return self::FORMAT === $format;
    }
}
