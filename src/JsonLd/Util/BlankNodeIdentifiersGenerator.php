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

namespace ApiPlatform\Core\JsonLd\Util;

/**
 * Generates blank node identifiers scoped to each JSON-LD document.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 *
 * @internal
 */
final class BlankNodeIdentifiersGenerator
{
    const IDENTIFIER_PREFIX = '_:b';

    private $blankNodeCounts = [];
    private $identifiers = [];

    /**
     * Gets a blank node identifier for an object, scoped to a JSON-LD document.
     *
     * @param object $object
     * @param string $documentRootHash
     *
     * @return string
     */
    public function getBlankNodeIdentifier($object, string $documentRootHash): string
    {
        $objectHash = spl_object_hash($object);

        if (!isset($this->identifiers[$documentRootHash][$objectHash])) {
            $this->blankNodeCounts[$documentRootHash] ?? $this->blankNodeCounts[$documentRootHash] = 0;
            $this->identifiers[$documentRootHash] ?? $this->identifiers[$documentRootHash] = [];

            $this->identifiers[$documentRootHash][$objectHash] = sprintf('%s%d', self::IDENTIFIER_PREFIX, $this->blankNodeCounts[$documentRootHash]++);
        }

        return $this->identifiers[$documentRootHash][$objectHash];
    }
}
