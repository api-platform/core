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

namespace ApiPlatform\Core\EventListener;

class_exists(\ApiPlatform\Symfony\EventListener\QueryParameterValidateListener::class);

if (false) {
    final class QueryParameterValidateListener extends \ApiPlatform\Symfony\EventListener\QueryParameterValidateListener
    {
    }
}
