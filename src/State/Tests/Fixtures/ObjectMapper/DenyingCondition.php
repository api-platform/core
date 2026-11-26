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

namespace ApiPlatform\State\Tests\Fixtures\ObjectMapper;

final class DenyingCondition
{
    public static function deny(mixed $value, object $source, ?object $target): bool
    {
        return false;
    }
}
