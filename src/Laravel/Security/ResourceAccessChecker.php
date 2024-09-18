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
        // ugly way to handle collection
        if (($object = $extraVariables['object']) instanceof Paginator) {
            // do not deny access if no items are found
            if (0 === $object->count()) {
                return true;
            }

            // we're only checking access to the first item
            foreach ($object as $obj) {
                return Gate::allows($expression, $obj);
            }
        }

        return Gate::allows($expression, $object);
    }
}
