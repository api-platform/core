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

namespace ApiPlatform\Serializer;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder as BaseJsonEncoder;

/**
 * A JSON encoder with appropriate default options to embed the generated document into HTML.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class JsonEncoder implements EncoderInterface, DecoderInterface
{
    // @noRector \Rector\Php81\Rector\Property\ReadOnlyPropertyRector
    public function __construct(private readonly string $format, private ?BaseJsonEncoder $jsonEncoder = null)
    {
        if (null !== $this->jsonEncoder) {
            return;
        }

        $this->jsonEncoder = new BaseJsonEncoder(
            // Encode <, >, ', &, and " characters in the JSON, making it also safe to be embedded into HTML.
            new JsonEncode(['json_encode_options' => \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_AMP | \JSON_HEX_QUOT | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_IGNORE]),
            new JsonDecode(['json_decode_associative' => true])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format, array $context = []): bool
    {
        return $this->format === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = []): string
    {
        return $this->jsonEncoder->encode($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format, array $context = []): bool
    {
        return $this->format === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data, $format, array $context = []): mixed
    {
        return $this->jsonEncoder->decode($data, $format, $context);
    }
}
