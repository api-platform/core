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

namespace ApiPlatform\JsonLd\Serializer;

use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Serializer\ContextTrait;

/**
 * Shared functionality for JSON-LD item normalization and denormalization.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
trait ItemNormalizerTrait
{
    use ClassInfoTrait;
    use ContextTrait;
    use JsonLdContextTrait;

    public const FORMAT = 'jsonld';
    private const JSONLD_KEYWORDS = [
        '@context',
        '@direction',
        '@graph',
        '@id',
        '@import',
        '@included',
        '@index',
        '@json',
        '@language',
        '@list',
        '@nest',
        '@none',
        '@prefix',
        '@propagate',
        '@protected',
        '@reverse',
        '@set',
        '@type',
        '@value',
        '@version',
        '@vocab',
    ];

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsNormalization($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? parent::getSupportedTypes($format) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * Gets allowed attributes for denormalization, including JSON-LD keywords.
     */
    protected function getAllowedAttributes(string|object $classOrObject, array $context, bool $attributesAsString = false): array|bool
    {
        $allowedAttributes = parent::getAllowedAttributes($classOrObject, $context, $attributesAsString);
        if (\is_array($allowedAttributes) && ($context['api_denormalize'] ?? false)) {
            $allowedAttributes = array_merge($allowedAttributes, self::JSONLD_KEYWORDS);
        }

        return $allowedAttributes;
    }
}
