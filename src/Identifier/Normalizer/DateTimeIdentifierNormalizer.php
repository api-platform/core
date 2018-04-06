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

namespace ApiPlatform\Core\Identifier\Normalizer;

use ApiPlatform\Core\Exception\InvalidIdentifierException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class DateTimeIdentifierNormalizer extends DateTimeNormalizer implements DenormalizerInterface
{
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        try {
            return parent::denormalize($data, $class, $format, $context);
        } catch (InvalidArgumentException $e) {
            throw new InvalidIdentifierException($e->getMessage());
        }
    }
}
