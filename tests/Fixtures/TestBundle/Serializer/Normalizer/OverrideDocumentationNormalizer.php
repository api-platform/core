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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class OverrideDocumentationNormalizer implements NormalizerInterface
{
    public function __construct(private readonly NormalizerInterface $documentationNormalizer)
    {
    }

    /**
     * @throws ExceptionInterface
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $normalizedData = $this->documentationNormalizer->normalize($data, $format, $context);
        if (!\is_array($normalizedData)) {
            throw new UnexpectedValueException('Expected data to be an array');
        }

        if (isset($normalizedData['definitions'])) {
            $normalizedData['definitions']['RamseyUuidDummy']['properties']['id']['description'] = 'The dummy id';
        } else {
            $normalizedData['components']['schemas']['RamseyUuidDummy']['properties']['id']['description'] = 'The dummy id';
        }

        return $normalizedData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->documentationNormalizer->supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(?string $format): array
    {
        return [];
    }
}
