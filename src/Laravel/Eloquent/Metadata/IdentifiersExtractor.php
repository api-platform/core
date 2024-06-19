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

use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Operation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IdentifiersExtractor implements IdentifiersExtractorInterface
{
    public function __construct(private readonly IdentifiersExtractorInterface $inner)
    {
    }

    public function getIdentifiersFromItem(object $item, ?Operation $operation = null, array $context = []): array
    {
        if ($item instanceof BelongsTo) {
            return [$item->getOwnerKeyName() => $item->getParent()->{$item->getForeignKeyName()}];
        }

        return $this->inner->getIdentifiersFromItem($item, $operation, $context);
    }
}
