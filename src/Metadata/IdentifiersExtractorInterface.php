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

namespace ApiPlatform\Metadata;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\RuntimeException;

/**
 * Extracts identifiers for a given Resource according to the retrieved Metadata.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface IdentifiersExtractorInterface
{
    /**
     * Finds identifiers from an Item (object).
     *
     * @param array<string, mixed> $context
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     *
     * @return array<string, mixed>
     */
    public function getIdentifiersFromItem(object $item, ?Operation $operation = null, array $context = []): array;
}
