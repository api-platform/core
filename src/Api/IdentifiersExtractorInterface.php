<?php

declare(strict_types=1);

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Api;

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
     * @param object $item
     *
     * @throws RuntimeException
     *
     * @return array
     */
    public function getIdentifiersFromItem($item): array;
}
