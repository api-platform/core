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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Parameter;

use ApiPlatform\Metadata\QueryParameter;

/**
 * Distinct class so an operator-map filter can share an HTTP key with a scalar filter:
 * Parameters dedup by (key, parameter class), so two parameters on the same key must differ in class.
 */
final class OperatorMapQueryParameter extends QueryParameter
{
}
