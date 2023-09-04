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

use ApiPlatform\Exception\RuntimeException;

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
     * @throws RuntimeException
     */
    public function getIdentifiersFromItem(object $item, Operation $operation = null, array $context = []): array;
}
