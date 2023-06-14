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
use Symfony\Component\Serializer\Encoder\YamlEncoder as BaseYamlEncoder;

/**
 * A YAML encoder with appropriate default options to embed the generated document into HTML.
 */
final class YamlEncoder implements EncoderInterface, DecoderInterface
{
    public function __construct(private readonly string $format = 'yamlopenapi', private readonly EncoderInterface&DecoderInterface $yamlEncoder = new BaseYamlEncoder())
    {
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
        return $this->yamlEncoder->encode($data, $format, $context);
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
        return $this->yamlEncoder->decode($data, $format, $context);
    }
}
