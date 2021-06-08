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
 * @ApiResource(
 *     collectionOperations={
 *         "get"={"method"="GET"},
 *         "post"={"path"="dummy_validation.{_format}", "method"="POST"},
 *         "post_validation_groups"={"route_name"="post_validation_groups", "validation_groups"={"a"}, "method"="GET"},
 *         "post_validation_sequence"={"route_name"="post_validation_sequence", "validation_groups"="app.dummy_validation.group_generator", "method"="GET"}
 *     }
 * )
 */
class DummyValidation extends Model
{
    public $timestamps = false;

    protected $apiProperties = [
        'name',
        'title',
        'code',
    ];
}
