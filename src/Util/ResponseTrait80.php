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

namespace ApiPlatform\Util;

trait ResponseTrait80
{
    public function getInfo(?string $type = null): mixed
    {
        if ($type) {
            return $this->info[$type] ?? null;
        }

        return $this->info;
    }
}
