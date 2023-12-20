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

namespace ApiPlatform\ParameterValidator\Validator;

/**
 * @internal
 */
trait CheckFilterDeprecationsTrait
{
    protected function checkFilterDeprecations(array $filterDescription): void
    {
        if (\array_key_exists('swagger', $filterDescription)) {
            trigger_deprecation(
                'api-platform/core',
                '3.0',
                'Using the "swagger" key in filters description is deprecated, use "openapi" instead.'
            );
        }
    }
}
