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

namespace ApiPlatform\Core\Util;

if (\PHP_VERSION_ID >= 80000) {
    trait ClientTrait
    {
        use ClientTrait80;
    }
} else {
    trait ClientTrait
    {
        use ClientTrait72;
    }
}
