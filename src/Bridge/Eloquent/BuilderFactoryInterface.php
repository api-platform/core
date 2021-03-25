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

use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

/**
 * Create a query or a schema builder from an Eloquent model.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface BuilderFactoryInterface
{
    /**
     * @param class-string<Model> $modelClass
     */
    public function getQueryBuilder(string $modelClass): QueryBuilder;

    /**
     * @param class-string<Model> $modelClass
     */
    public function getSchemaBuilder(string $modelClass): SchemaBuilder;
}
