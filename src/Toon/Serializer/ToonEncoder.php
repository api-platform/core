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

namespace ApiPlatform\Toon\Serializer;

use HelgeSverre\Toon\Toon;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Encodes and decodes data in Toon format.
 *
 * Toon is an encoding format (like JSON/XML) that can be used with any representation format
 * (JSON-LD, JSON:API, HAL, Hydra). This encoder works with normalized data from those formats.
 *
 * @author API Platform Community
 */
final class ToonEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'toon';

    // Supported format combinations: representation+encoding
    private const SUPPORTED_FORMATS = [
        'toon',           // JSON-LD structure with Toon encoding (text/ld+toon)
        'jsonhal_toon',   // HAL + Toon (text/hal+toon)
        'jsonapi_toon',   // JSON:API + Toon (text/vnd.api+toon)
        'hydra_toon',     // Hydra + Toon
        'jsonopenapi_toon', // OpenAPI + Toon (text/vnd.openapi+toon)
    ];

    /**
     * {@inheritdoc}
     */
    public function encode(mixed $data, string $format, array $context = []): string
    {
        return Toon::encode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding(string $format, array $context = []): bool
    {
        return \in_array($format, self::SUPPORTED_FORMATS, true);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(string $data, string $format, array $context = []): mixed
    {
        return Toon::decode($data);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding(string $format, array $context = []): bool
    {
        return \in_array($format, self::SUPPORTED_FORMATS, true);
    }
}
