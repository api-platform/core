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

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IdentifiersExtractor implements IdentifiersExtractorInterface
{
    public function __construct(private readonly IdentifiersExtractorInterface $inner)
    {
    }

    public function getIdentifiersFromItem(object $item, ?Operation $operation = null, array $context = []): array
    {
        if (!($item instanceof BelongsTo || $item instanceof Model) || !$operation instanceof HttpOperation) {
            return $this->inner->getIdentifiersFromItem($item, $operation, $context);
        }

        $identifiers = [];
        foreach ($operation->getUriVariables() ?? [] as $link) {
            $parameterName = $link->getParameterName();
            $identifiers[$parameterName] = $this->getIdentifierValue($item, $link);
        }

        return $identifiers;
    }

    private function getIdentifierValue(object $item, Link $link): mixed
    {
        if ($item instanceof ($link->getFromClass())) {
            return $item->{$link->getIdentifiers()[0]};
        }

        if ($item instanceof BelongsTo) {
            return $item->getParent()->{$item->getForeignKeyName()};
        }

        if ($toProperty = $link->getToProperty()) {
            $relation = $item->{$toProperty}();

            if ($relation instanceof BelongsTo) {
                return $item->{$relation->getForeignKeyName()};
            }
        }

        return $item->{$link->getIdentifiers()[0]};
    }
}
