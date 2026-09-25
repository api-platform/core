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

use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Gate;

class ResourceAccessChecker implements ResourceAccessCheckerInterface
{
    public function isGranted(string $resourceClass, string $expression, array $extraVariables = []): bool
    {
        $object = $extraVariables['object'] ?? null;

        // A collection operation authorizes against the resource class, not the concrete
        // result. That result can be a Paginator or a PartialPaginator (both implement
        // PartialPaginatorInterface), or a plain Eloquent Collection (an Enumerable) when
        // pagination is disabled; all of them must resolve to the resource class so the
        // policy's collection ability (e.g. viewAny()) is evaluated instead of an item one.
        $isCollection = $object instanceof PartialPaginatorInterface || $object instanceof Enumerable;

        return Gate::allows(
            $expression,
            ($isCollection || null === $object) ? $resourceClass : $object
        );
    }
}
