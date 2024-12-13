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

use ApiPlatform\Metadata\Operation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template T of Model
 */
interface LinksHandlerInterface
{
    /**
     * Handles Laravel links.
     *
     * @param Builder<T>                                                           $builder
     * @param array<string, mixed>                                                 $uriVariables
     * @param array{modelClass: string, operation: Operation}|array<string, mixed> $context
     *
     * @return Builder<T>
     */
    public function handleLinks(Builder $builder, array $uriVariables, array $context): Builder;
}
