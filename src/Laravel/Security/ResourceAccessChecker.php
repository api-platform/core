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

namespace ApiPlatform\Laravel\Security;

use ApiPlatform\Laravel\Eloquent\Paginator;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use Illuminate\Support\Facades\Gate;

class ResourceAccessChecker implements ResourceAccessCheckerInterface
{
    public function isGranted(string $resourceClass, string $expression, array $extraVariables = []): bool
    {
        $object = $extraVariables['object'] ?? null;

        return Gate::allows(
            $expression,
            ($object instanceof Paginator || null === $object) ? $resourceClass : $object
        );
    }
}
