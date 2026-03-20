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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Serializer\Denormalizer;

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DummyDtoNameConverted;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class InputDtoDenormalizer implements DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = []): mixed
    {
        return new DummyDtoNameConverted(42);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return DummyDtoNameConverted::class === $type && 'child_relation' === $data;
    }

    public function getSupportedTypes($format): array
    {
        return [
            DummyDtoNameConverted::class => true,
        ];
    }
}
