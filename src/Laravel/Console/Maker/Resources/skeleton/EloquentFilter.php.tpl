<?php

declare(strict_types=1);

namespace {{ namespace }};

use ApiPlatform\Metadata\Parameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class {{ class_name }} implements FilterInterface
{
    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
    */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        // TODO: make your awesome query using the $builder
        // return $builder->
    }
}
