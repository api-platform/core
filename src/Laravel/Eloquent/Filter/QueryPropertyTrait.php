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

namespace ApiPlatform\Laravel\Eloquent\Filter;

use ApiPlatform\Metadata\Parameter;

/**
 * @internal
 */
trait QueryPropertyTrait
{
    private function getQueryProperty(Parameter $parameter): ?string
    {
        return $parameter->getExtraProperties()['_query_property'] ?? $parameter->getProperty() ?? null;
    }
}
