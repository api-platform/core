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
 * Third Level.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @ApiResource
 */
class ThirdLevel extends Model
{
    public $timestamps = false;

    public function fourthLevel(): BelongsTo
    {
        return $this->belongsTo(FourthLevel::class);
    }

    public function badFourthLevel(): BelongsTo
    {
        return $this->belongsTo(FourthLevel::class);
    }

    protected $apiProperties = [
        'id',
        'level' => ['groups' => ['barcelona', 'chicago']],
        'test',
        'fourthLevel' => ['groups' => ['barcelona', 'chicago', 'friends']],
        'badFourthLevel',
    ];

    protected $casts = [
        'level' => 'integer',
        'test' => 'boolean',
    ];

    protected $attributes = [
        'level' => 3,
        'test' => true,
    ];
}
