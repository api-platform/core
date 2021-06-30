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

namespace ApiPlatform\Translation;

/**
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface ResourceTranslatorInterface
{
    public function isResourceTranslatable(object $resource): bool;

    public function isResourceClassTranslatable(string $resourceClass): bool;

    public function isAllTranslationsEnabled(string $resourceClass, array $clientParameters): bool;

    public function getTranslationClass(string $resourceClass): string;

    public function getLocale(): ?string;

    /**
     * Returns either the translation of an attribute or all its translations indexed by locale.
     */
    public function translateAttributeValue(object $resource, string $attribute, array $context): array|string|null;
}
