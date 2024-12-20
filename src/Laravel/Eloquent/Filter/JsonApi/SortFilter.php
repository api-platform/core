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

namespace ApiPlatform\Laravel\Eloquent\Filter\JsonApi;

use ApiPlatform\Laravel\Eloquent\Filter\FilterInterface;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\ParameterProviderFilterInterface;
use ApiPlatform\Metadata\PropertiesAwareInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SortFilter implements FilterInterface, JsonSchemaFilterInterface, ParameterProviderFilterInterface, PropertiesAwareInterface
{
    public const ASC = 'asc';
    public const DESC = 'desc';

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        if (!\is_array($values)) {
            return $builder;
        }

        foreach ($values as $order => $dir) {
            if (self::ASC !== $dir && self::DESC !== $dir) {
                continue;
            }

            $builder->orderBy($order, $dir);
        }

        return $builder;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'string'];
    }

    public static function getParameterProvider(): string
    {
        return SortFilterParameterProvider::class;
    }
}
