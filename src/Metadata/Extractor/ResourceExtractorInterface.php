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

use ApiPlatform\Exception\InvalidArgumentException;

/**
 * Extracts an array of metadata from a file or a list of files.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
interface ResourceExtractorInterface
{
    /**
     * Parses all metadata files and convert them in an array.
     *
     * @throws InvalidArgumentException
     */
    public function getResources(): array;
}
