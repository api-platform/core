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

use ApiPlatform\Test\Constraint\ArraySubsetTrait as BaseArraySubsetTrait;

trigger_deprecation('api-platform/core', '5.0', 'The "%s" trait is deprecated, use "%s" instead.', ArraySubsetTrait::class, BaseArraySubsetTrait::class);

/**
 * @deprecated since API Platform 5.0, use {@see BaseArraySubsetTrait} instead
 */
trait ArraySubsetTrait
{
    use BaseArraySubsetTrait;
}
