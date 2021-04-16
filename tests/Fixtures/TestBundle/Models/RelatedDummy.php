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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Related Dummy.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource(
 *     iri="https://schema.org/Product",
 *     attributes={
 *         "normalization_context"={"groups"={"friends"}},
 *         "filters"={"related_dummy.friends", "related_dummy.complex_sub_query"}
 *     }
 * )
 */
class RelatedDummy extends ParentDummy
{
    public $timestamps = false;

    public static $snakeAttributes = false;

    public function thirdLevel(): BelongsTo
    {
        return $this->belongsTo(ThirdLevel::class);
    }

    public function relatedToDummyFriend(): HasMany
    {
        return $this->hasMany(RelatedToDummyFriend::class);
    }

    public function getEmbeddedDummyAttribute()
    {
        return [];
    }

    public function setEmbeddedDummyAttribute($value)
    {
    }

    protected $apiProperties = [
        'id' => ['groups' => ['chicago', 'friends']],
        'name' => ['groups' => ['friends']],
        'symfony' => ['groups' => ['barcelona', 'chicago', 'friends']],
        'dummyDate' => ['groups' => ['friends']],
        'thirdLevel' => ['relation' => ThirdLevel::class, 'groups' => ['barcelona', 'chicago', 'friends']],
        'relatedToDummyFriend' => ['groups' => ['fakemanytomany', 'friends']],
        'dummyBoolean' => ['groups' => ['friends']],
        'embeddedDummy' => ['groups' => ['friends']],
        'age' => ['groups' => ['friends']],
    ];

    protected $attributes = [
        'symfony' => 'symfony',
    ];

    protected $with = ['thirdLevel'];
}
