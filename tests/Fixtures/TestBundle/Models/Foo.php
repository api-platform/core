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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Models;

use ApiPlatform\Core\Annotation\ApiResource;
use Illuminate\Database\Eloquent\Model;

/**
 * Foo.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @ApiResource(
 *     attributes={
 *         "order"={"bar", "name"="DESC"}
 *     },
 *     graphql={
 *         "item_query",
 *         "collection_query"={"pagination_enabled"=false},
 *         "create",
 *         "delete"
 *     },
 *     collectionOperations={
 *         "get",
 *         "get_desc_custom"={"method"="GET", "path"="custom_collection_desc_foos", "order"={"name"="DESC"}},
 *         "get_asc_custom"={"method"="GET", "path"="custom_collection_asc_foos", "order"={ "name"="ASC"}},
 *     },
 *     properties={"id"}
 * )
 */
class Foo extends Model
{
    public $timestamps = false;
}
