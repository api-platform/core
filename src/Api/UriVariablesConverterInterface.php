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

class_exists(\ApiPlatform\Metadata\UriVariablesConverterInterface::class);

class_alias(
    \ApiPlatform\Metadata\UriVariablesConverterInterface::class,
    __NAMESPACE__.'\UriVariablesConverterInterface'
);

if (false) { // @phpstan-ignore-line
    interface UriVariablesConverterInterface extends \ApiPlatform\Metadata\UriVariablesConverterInterface
    {
    }
}
