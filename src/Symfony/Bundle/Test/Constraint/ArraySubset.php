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

namespace ApiPlatform\Symfony\Bundle\Test\Constraint;

use ApiPlatform\Test\Constraint\ArraySubset;

trigger_deprecation('api-platform/core', '5.0', 'The "%s" class is deprecated, use "%s" instead.', 'ApiPlatform\Symfony\Bundle\Test\Constraint\ArraySubset', ArraySubset::class);

class_alias(ArraySubset::class, 'ApiPlatform\Symfony\Bundle\Test\Constraint\ArraySubset');
