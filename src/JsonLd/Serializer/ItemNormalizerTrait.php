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

use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * Shared denormalization logic for the JSON-LD item (de)normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
trait ItemNormalizerTrait
{
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
     * @throws NotNormalizableValueException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['@id']) && !isset($context[AbstractItemNormalizer::OBJECT_TO_POPULATE])) {
            if (true !== ($context['api_allow_update'] ?? true)) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            try {
                $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri($data['@id'], $context + ['fetch_data' => true], $context['operation'] ?? null);
            } catch (ItemNotFoundException $e) {
                $operation = $context['operation'] ?? null;

                if (!('PUT' === $operation?->getMethod() && ($operation->getExtraProperties()['standard_put'] ?? true))) {
                    throw $e;
                }
            }
        }

        return parent::denormalize($data, $type, $format, $context);
    }

    protected function getAllowedAttributes(string|object $classOrObject, array $context, bool $attributesAsString = false): array|bool
    {
        $allowedAttributes = parent::getAllowedAttributes($classOrObject, $context, $attributesAsString);
        if (\is_array($allowedAttributes) && ($context['api_denormalize'] ?? false)) {
            $allowedAttributes = array_merge($allowedAttributes, self::JSONLD_KEYWORDS);
        }

        return $allowedAttributes;
    }
}
