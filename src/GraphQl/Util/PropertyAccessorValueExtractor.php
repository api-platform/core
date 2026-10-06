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

namespace ApiPlatform\GraphQl\Util;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @internal
 */
final class PropertyAccessorValueExtractor
{
    private static ?PropertyAccessorInterface $propertyAccessor = null;

    public static function getValue(object $object, string $property, ?IdentifiersExtractorInterface $identifiersExtractor = null, ?ResourceClassResolverInterface $resourceClassResolver = null): string
    {
        self::$propertyAccessor ??= PropertyAccess::createPropertyAccessor();

        $value = self::$propertyAccessor->getValue($object, $property);
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (\is_object($value) && (!$value instanceof \Stringable || ($resourceClassResolver?->isResourceClass($value::class) ?? false))) {
            if (null === $identifiersExtractor) {
                throw new \LogicException('An identifiers extractor is required to resolve object-valued subscription private fields.');
            }

            $identifiers = $identifiersExtractor->getIdentifiersFromItem($value);
            if ([] === $identifiers) {
                throw new RuntimeException(\sprintf('No identifiers found for private field "%s".', $property));
            }

            if (1 === \count($identifiers)) {
                $value = reset($identifiers);
            } else {
                ksort($identifiers);
                $value = $identifiers;
            }
        }

        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (null === $value) {
            return 'null';
        }

        if ($value instanceof \Stringable || \is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, \JSON_THROW_ON_ERROR);
    }
}
