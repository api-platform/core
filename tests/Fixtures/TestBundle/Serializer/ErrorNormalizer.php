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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Serializer;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ErrorNormalizer implements NormalizerInterface
{
    public function __construct(private readonly NormalizerInterface $decorated)
    {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|\ArrayObject
    {
        $a = $this->decorated->normalize($object, $format, $context);
        $a['hello'] = 'world';

        return $a;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return 'json' === $format;
    }

    public function getSupportedTypes(?string $format): array
    {
        if ('json' === $format) {
            return [
                FlattenException::class => true,
            ];
        }

        return [];
    }
}
