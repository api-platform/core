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

namespace ApiPlatform\Laravel\Eloquent\Metadata;

use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Illuminate\Database\Eloquent\Relations\Relation;

final class ResourceClassResolver implements ResourceClassResolverInterface
{
    public function __construct(
        private readonly ResourceClassResolverInterface $inner,
    ) {
    }

    public function getResourceClass(mixed $value, ?string $resourceClass = null, bool $strict = false): string
    {
        if ($value instanceof Relation) {
            return $this->inner->getResourceClass($value->getRelated());
        }

        return $this->inner->getResourceClass($value, $resourceClass, $strict);
    }

    public function isResourceClass(string $type): bool
    {
        return $this->inner->isResourceClass($type);
    }
}
