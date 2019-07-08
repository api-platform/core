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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Exception\InvalidArgumentException;

/**
 * Normalizes the "formats" attributes.
 *
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
trait FormatsNormalizerTrait
{
    /**
     * @throws InvalidArgumentException
     */
    private function normalizeFormats(array $currentFormats, array $configuredFormats): array
    {
        $normalizedFormats = [];
        foreach ($currentFormats as $format => $value) {
            if (!is_numeric($format)) {
                $normalizedFormats[$format] = (array) $value;
                continue;
            }
            if (!\is_string($value)) {
                throw new InvalidArgumentException(sprintf("The 'formats' attributes value must be a string when trying to include an already configured format, %s given.", \gettype($value)));
            }
            if (\array_key_exists($value, $configuredFormats)) {
                $normalizedFormats[$value] = $configuredFormats[$value];
                continue;
            }

            throw new InvalidArgumentException(sprintf("You either need to add the format '%s' to your project configuration or declare a mime type for it in your annotation.", $value));
        }

        return $normalizedFormats;
    }

    private function normalizeInputOutput(array $inOut, array $defaultFormats): array
    {
        $inOut['formats'] = isset($inOut['formats']) ? $this->normalizeFormats($inOut['formats'], $this->formats) : $defaultFormats;

        return $inOut;
    }
}
