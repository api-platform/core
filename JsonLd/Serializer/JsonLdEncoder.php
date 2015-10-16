<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * JSON-LD Encoder.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class JsonLdEncoder extends JsonEncoder
{
    const FORMAT = 'jsonld';

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format)
    {
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding($format)
    {
        return self::FORMAT === $format;
    }
}
