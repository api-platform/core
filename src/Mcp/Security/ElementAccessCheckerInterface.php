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

namespace ApiPlatform\Mcp\Security;

/**
 * Decides whether the current caller may see a tool or resource in list results.
 *
 * @experimental
 */
interface ElementAccessCheckerInterface
{
    public function isGranted(string $operationName): bool;
}
