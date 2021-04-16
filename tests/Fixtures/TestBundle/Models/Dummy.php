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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     attributes={
 *         "filters"={
 *             "my_dummy.boolean",
 *             "my_dummy.date",
 *             "my_dummy.exists",
 *             "my_dummy.numeric",
 *             "my_dummy.order",
 *             "my_dummy.range",
 *             "my_dummy.search",
 *             "my_dummy.property"
 *         }
 *     }
 * )
 */
class Dummy extends Model
{
    public $timestamps = false;

    public static $snakeAttributes = false;

    public function relatedDummy(): BelongsTo
    {
        return $this->belongsTo(RelatedDummy::class);
    }

    public function relatedDummies(): HasMany
    {
        return $this->hasMany(RelatedDummy::class);
    }

    public function relatedOwnedDummy(): HasOne
    {
        return $this->hasOne(RelatedOwnedDummy::class);
    }

    public function relatedOwningDummy(): HasOne
    {
        return $this->hasOne(RelatedOwningDummy::class);
    }

    protected $apiProperties = [
        'name',
        'alias',
        'foo',
        'description',
        'dummy',
        'dummyBoolean',
        'dummyDate',
        'dummyFloat',
        'dummyPrice',
        'relatedDummy' => ['relation' => RelatedDummy::class],
        'relatedDummies' => ['relationMany' => RelatedDummy::class],
        'jsonData',
        'arrayData',
        'nameConverted',
        'relatedOwnedDummy' => ['relation' => RelatedOwnedDummy::class],
        'relatedOwningDummy' => ['relation' => RelatedOwningDummy::class],
    ];

    protected $attributes = [
        'jsonData' => '[]',
        'arrayData' => '[]',
    ];

    protected $casts = [
        'jsonData' => 'array',
        'arrayData' => 'array',
        'dummyDate' => 'datetime',
    ];
}
