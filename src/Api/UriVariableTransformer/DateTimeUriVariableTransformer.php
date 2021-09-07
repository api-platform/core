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

namespace ApiPlatform\Api\UriVariableTransformer;

use ApiPlatform\Api\UriVariableTransformerInterface;
use ApiPlatform\Exception\InvalidUriVariableException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

final class DateTimeUriVariableTransformer implements UriVariableTransformerInterface
{
    private $dateTimeNormalizer;

    public function __construct()
    {
        $this->dateTimeNormalizer = new DateTimeNormalizer();
    }

    public function transform($value, array $types, array $context = [])
    {
        try {
            return $this->dateTimeNormalizer->denormalize($value, $types[0], null, $context);
        } catch (NotNormalizableValueException $e) {
            throw new InvalidUriVariableException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supportsTransformation($value, array $types, array $context = []): bool
    {
        return $this->dateTimeNormalizer->supportsDenormalization($value, $types[0]);
    }
}
