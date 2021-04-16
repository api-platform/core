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

namespace ApiPlatform\Core\Bridge\Eloquent;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use ApiPlatform\Core\Util\ClassInfoTrait;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Data persister for Eloquent.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class DataPersister implements ContextAwareDataPersisterInterface
{
    use ClassInfoTrait;

    private $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($data, array $context = []): bool
    {
        return \is_object($data) && is_subclass_of($this->getObjectClass($data), Model::class, true);
    }

    /**
     * {@inheritdoc}
     *
     * @param Model $data
     */
    public function persist($data, array $context = [])
    {
        $this->databaseManager->connection()->transaction(function () use ($data) {
            $this->save($data);
        });

        $data->refresh();

        return $data;
    }

    /**
     * {@inheritdoc}
     *
     * @param Model $data
     */
    public function remove($data, array $context = []): void
    {
        $data->delete();
    }

    private function save(Model $data): void
    {
        $data->save();

        foreach ($data->getRelations() as $relationName => $relation) {
            if (null === $relation) {
                continue;
            }

            $related = $data->{$relationName}();

            if (is_a($related, HasOne::class)) {
                $related->save($relation);
            }
            if (is_iterable($relation) && is_a($related, HasMany::class)) {
                $related->saveMany($relation);
            }

            if (is_iterable($relation)) {
                foreach ($relation as $relationItem) {
                    $this->save($relationItem);
                }
            } else {
                $this->save($relation);
            }

            if (is_a($related, BelongsTo::class)) {
                $related->associate($relation);
            }
        }

        $data->push();
    }
}
