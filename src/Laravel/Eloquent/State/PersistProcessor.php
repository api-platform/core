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

namespace ApiPlatform\Laravel\Eloquent\State;

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @implements ProcessorInterface<\Illuminate\Database\Eloquent\Model, \Illuminate\Database\Eloquent\Model>
 */
final class PersistProcessor implements ProcessorInterface
{
    /**
     * @var array<string, string>
     */
    private array $relations;

    public function __construct(
        private readonly ModelMetadata $modelMetadata,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $toMany = [];

        foreach ($this->modelMetadata->getRelations($data) as $relation) {
            if (!isset($data->{$relation['name']})) {
                continue;
            }

            if (BelongsTo::class === $relation['type'] || MorphTo::class === $relation['type']) {
                $rel = $data->{$relation['name']};

                if (!$rel->exists) {
                    $rel->save();
                }

                $data->{$relation['method_name']}()->associate($data->{$relation['name']});
                unset($data->{$relation['name']});
                $this->relations[$relation['method_name']] = $relation['name'];
            }

            if (HasMany::class === $relation['type'] || MorphMany::class === $relation['type']) {
                $rel = $data->{$relation['name']};

                if (!\is_array($rel) && !$rel instanceof Collection) {
                    throw new RuntimeException('To-Many relationship is not a collection.');
                }

                $toMany[$relation['method_name']] = $rel;
                unset($data->{$relation['name']});
                $this->relations[$relation['method_name']] = $relation['name'];
            }
        }

        if (($previousData = $context['previous_data'] ?? null) && $operation instanceof HttpOperation && 'PUT' === $operation->getMethod() && ($operation->getExtraProperties()['standard_put'] ?? true)) {
            foreach ($this->modelMetadata->getAttributes($data) as $attribute) {
                if ($attribute['primary'] ?? false) {
                    $data->{$attribute['name']} = $previousData->{$attribute['name']};
                }
            }
            $data->exists = true;
        }

        $data->saveOrFail();
        $data->refresh();

        foreach ($data->getRelations() as $methodName => $obj) {
            if (isset($this->relations[$methodName])) {
                $data->{$this->relations[$methodName]} = $obj;
            }
        }

        foreach ($toMany as $methodName => $relations) {
            $data->{$methodName}()->saveMany($relations);
            $data->{$this->relations[$methodName]} = $relations;
            unset($toMany[$methodName]);
        }

        return $data;
    }
}
