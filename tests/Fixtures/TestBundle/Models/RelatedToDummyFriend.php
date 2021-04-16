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
 * Related To Dummy Friend represent an association table for a manytomany relation.
 *
 * @ApiResource(
 *     attributes={"normalization_context"={"groups"={"fakemanytomany"}}, "filters"={"related_to_dummy_friend.name"}}
 * )
 */
class RelatedToDummyFriend extends Model
{
    public $timestamps = false;

    public function dummyFriend(): BelongsTo
    {
        return $this->belongsTo(DummyFriend::class);
    }

    public function relatedDummy(): BelongsTo
    {
        return $this->belongsTo(RelatedDummy::class);
    }

    protected $apiProperties = [
        'name' => ['groups' => ['fakemanytomany', 'friends']],
        'description' => ['groups' => ['fakemanytomany', 'friends']],
        'dummyFriend' => ['relation' => DummyFriend::class, 'groups' => ['fakemanytomany', 'friends']],
        'relatedDummy' => ['relation' => RelatedDummy::class],
    ];
}
