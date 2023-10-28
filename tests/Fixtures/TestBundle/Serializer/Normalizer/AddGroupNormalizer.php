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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelationGroupImpactOnCollection;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class AddGroupNormalizer implements NormalizerAwareInterface, NormalizerInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'RELATED_GROUP_IMPACT_ON_COLLECTION_NORMALIZER_ALREADY_CALLED';

    public function normalize($object, $format = null, array $context = []): array|string|int|float|bool|\ArrayObject
    {
        $context[self::ALREADY_CALLED] = true;
        if (!($operation = $context['operation'] ?? null)) {
            return $this->normalizer->normalize($object, $format, $context);
        }

        if ($operation instanceof Get && '/custom_normalizer_relation_group_impact_on_collection' === $operation->getUriTemplate()) {
            $context['groups'] = ['related'];
        }

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        // Make sure we're not called twice
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof RelationGroupImpactOnCollection;
    }

    public function getSupportedTypes($format): array
    {
        return [
            RelationGroupImpactOnCollection::class => false,
        ];
    }
}
