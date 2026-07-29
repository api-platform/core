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

namespace ApiPlatform\Elasticsearch\Esql\Extension;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Metadata\Operation;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Applies the operation default order ("order" attribute) when no sort was
 * requested, then always adds a sort on "_id" as a tiebreaker so results are
 * deterministic across pages.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class SortExtension implements CollectionExtensionInterface
{
    public function __construct(
        private readonly ?NameConverterInterface $nameConverter = null,
        private readonly ?string $defaultDirection = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!$query->hasSort()) {
            $order = $operation?->getOrder() ?? [];
            if (!$order && null !== $this->defaultDirection) {
                $order = ['_id' => $this->defaultDirection];
            }

            foreach ($order as $property => $direction) {
                if (\is_int($property)) {
                    $property = $direction;
                    $direction = 'ASC';
                }

                $field = '_id' === $property || null === $this->nameConverter ? $property : $this->nameConverter->normalize($property, $resourceClass, null, $context);
                $query->sort($field, $direction);
            }
        }

        // deterministic tiebreaker, required for stable pagination
        if (!$query->hasSortOn('_id')) {
            $query->sort('_id');
        }
    }
}
