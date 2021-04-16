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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={"groups"={"chicago"}},
 *         "denormalization_context"={"groups"={"chicago"}},
 *     },
 *     itemOperations={
 *         "get",
 *         "patch"={"input_formats"={"json"={"application/merge-patch+json"}, "jsonapi"}}
 *     }
 * )
 */
class PatchDummyRelation extends Model
{
    public $timestamps = false;

    public function related(): BelongsTo
    {
        return $this->belongsTo(RelatedDummy::class);
    }

    protected $apiProperties = [
        'related' => ['relation' => RelatedDummy::class, 'groups' => ['chicago']],
    ];

    protected $with = ['related'];
}
