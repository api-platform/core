<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Serializer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The {@link \DateTime} object normalizer.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class DateTimeNormalizer implements NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return 'json-ld' === $format && $data instanceof \DateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return $object->format(\DateTime::ATOM);
    }
}
