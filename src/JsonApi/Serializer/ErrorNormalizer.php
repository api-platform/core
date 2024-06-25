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

namespace ApiPlatform\JsonApi\Serializer;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts {@see \Exception} or {@see FlattenException} or to a JSON API error representation.
 *
 * @author Héctor Hurtarte <hectorh30@gmail.com>
 */
final class ErrorNormalizer implements NormalizerInterface
{
    public const FORMAT = 'jsonapi';

    public function __construct(private ?NormalizerInterface $itemNormalizer = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        $jsonApiObject = $this->itemNormalizer->normalize($object, $format, $context);
        $error = $jsonApiObject['data']['attributes'];
        $error['id'] = $jsonApiObject['data']['id'];
        $error['type'] = $jsonApiObject['data']['id'];

        return ['errors' => [$error]];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && ($data instanceof \Exception || $data instanceof FlattenException);
    }

    public function getSupportedTypes($format): array
    {
        if (self::FORMAT === $format) {
            return [
                \Exception::class => true,
                FlattenException::class => true,
            ];
        }

        return [];
    }
}
