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
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @implements ProcessorInterface<\Illuminate\Database\Eloquent\Model, \Illuminate\Database\Eloquent\Model>
 */
final class PersistProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ModelMetadata $modelMetadata,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        foreach ($this->modelMetadata->getRelations($data) as $relation) {
            if (!isset($data->{$relation['name']})) {
                continue;
            }

            if (BelongsTo::class === $relation['type']) {
                $data->{$relation['name']}()->associate($data->{$relation['name']});
                unset($data->{$relation['name']});
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

        return $data;
    }
}
