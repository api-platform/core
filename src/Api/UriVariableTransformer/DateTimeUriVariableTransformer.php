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

use ApiPlatform\Api\UriVariableConverterInterface;
use ApiPlatform\Api\UriVariableTransformerInterface;
use ApiPlatform\Exception\InvalidUriVariableException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\TypeInfo\Type;

final class DateTimeUriVariableTransformer implements UriVariableTransformerInterface, UriVariableConverterInterface
{
    private readonly DateTimeNormalizer $dateTimeNormalizer;

    public function __construct()
    {
        $this->dateTimeNormalizer = new DateTimeNormalizer();
    }

    /**
     * {@inheritdoc}
     */
    public function transform(mixed $value, array $types, array $context = []): \DateTimeInterface
    {
        trigger_deprecation('api-platform/elasticsearch', '3.3', 'The "%s()" method is deprecated, use "TODO mtarld()" instead.', __METHOD__);

        try {
            return $this->dateTimeNormalizer->denormalize($value, $types[0], null, $context);
        } catch (NotNormalizableValueException $e) {
            throw new InvalidUriVariableException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation(mixed $value, array $types, array $context = []): bool
    {
        trigger_deprecation('api-platform/elasticsearch', '3.3', 'The "%s()" method is deprecated, use "TODO mtarld()" instead.', __METHOD__);

        return $this->dateTimeNormalizer->supportsDenormalization($value, $types[0]);
    }

    public function convert(mixed $value, Type $type, array $context = []): \DateTimeInterface
    {
        try {
            return $this->dateTimeNormalizer->denormalize($value, (string) $type, null, $context);
        } catch (NotNormalizableValueException $e) {
            throw new InvalidUriVariableException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supportsConversion(mixed $value, Type $type, array $context = []): bool
    {
        return $this->dateTimeNormalizer->supportsDenormalization($value, (string) $type);
    }
}
