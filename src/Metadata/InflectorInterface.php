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

interface InflectorInterface
{
    /**
     * Returns a snake case transformed string.
     */
    public function tableize(string $input): string;

    /**
     * Returns the plural forms of a string.
     */
    public function pluralize(string $singular): string;
}
