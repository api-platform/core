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

if (\PHP_VERSION_ID < 80000) {
    trait ResponseTrait
    {
        use ResponseTrait71;
    }
} else {
    trait ResponseTrait
    {
        use ResponseTrait80;
    }
}

class_alias(ResponseTrait::class, \ApiPlatform\Core\Util\ResponseTrait::class);
