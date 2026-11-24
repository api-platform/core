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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

use ApiPlatform\Metadata\IriConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class RuntimeObjectNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const DEPTH = 'runtime_resource_depth';
    private const CURRENT = 'runtime_resource_current';

    public function __construct(private readonly IriConverterInterface $iriConverter)
    {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $depth = $context[self::DEPTH] ?? 0;
        if ($depth > 1) {
            return $this->iriConverter->getIriFromResource($data);
        }

        return $this->normalizer->normalize($data, $format, [self::DEPTH => $depth + 1, self::CURRENT => $data] + $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof RuntimeObject && ($context[self::CURRENT] ?? null) !== $data;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [RuntimeObject::class => false];
    }
}
