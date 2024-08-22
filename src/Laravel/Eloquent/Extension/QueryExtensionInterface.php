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

namespace ApiPlatform\Laravel\Eloquent\Extension;

use ApiPlatform\Metadata\Operation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface QueryExtensionInterface
{
    /**
     * @param Builder<Model>        $builder
     * @param array<string, string> $uriVariables
     * @param array<string, mixed>  $context
     *
     * @return Builder<Model>
     */
    public function apply(Builder $builder, array $uriVariables, Operation $operation, $context = []): Builder;
}
