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
final class BuilderFactory implements BuilderFactoryInterface
{
    public function getQueryBuilder(string $modelClass): QueryBuilder
    {
        return $modelClass::query();
    }

    public function getSchemaBuilder(string $modelClass): SchemaBuilder
    {
        /** @var Model $model */
        $model = new $modelClass();

        return $model->getConnection()->getSchemaBuilder();
    }
}
