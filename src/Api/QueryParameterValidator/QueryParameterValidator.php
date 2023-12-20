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

namespace ApiPlatform\Api\QueryParameterValidator;

use ApiPlatform\ParameterValidator\ParameterValidator as NewQueryParameterValidator;

/**
 * Validates query parameters depending on filter description.
 *
 * @deprecated use ApiPlatform\QueryParameterValidator\QueryParameterValidator instead
 */
class QueryParameterValidator extends NewQueryParameterValidator
{
}
