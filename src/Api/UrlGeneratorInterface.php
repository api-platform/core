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

namespace ApiPlatform\Api;

class_alias(
    \ApiPlatform\Metadata\UrlGeneratorInterface::class,
    __NAMESPACE__.'\UrlGeneratorInterface'
);

if (false) { // @phpstan-ignore-line
    interface UrlGeneratorInterface extends \ApiPlatform\Metadata\UrlGeneratorInterface
    {
    }
}
