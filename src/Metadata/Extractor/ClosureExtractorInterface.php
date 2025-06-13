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

namespace ApiPlatform\Metadata\Extractor;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;

/**
 * Extracts an array of closure from a file or a list of files.
 *
 * @author Loïc Frémont <lc.fremont@gmail.com>
 */
interface ClosureExtractorInterface
{
    /**
     * Parses all metadata files and convert them in an array.
     *
     * @throws InvalidArgumentException
     */
    public function getClosures(): array;
}
