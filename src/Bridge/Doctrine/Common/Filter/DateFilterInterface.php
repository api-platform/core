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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Filter;

/**
 * Interface for filtering the collection by date intervals.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface DateFilterInterface
{
    const PARAMETER_BEFORE = 'before';
    const PARAMETER_STRICTLY_BEFORE = 'strictly_before';
    const PARAMETER_AFTER = 'after';
    const PARAMETER_STRICTLY_AFTER = 'strictly_after';
    const EXCLUDE_NULL = 'exclude_null';
    const INCLUDE_NULL_BEFORE = 'include_null_before';
    const INCLUDE_NULL_AFTER = 'include_null_after';
    const INCLUDE_NULL_BEFORE_AND_AFTER = 'include_null_before_and_after';
}
