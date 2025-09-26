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

namespace Workbench\App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workbench\App\Http\Requests\StoreSlotRequest;

#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(
            rules: StoreSlotRequest::class,
        ),
        new Put(),
        new Patch(),
        new Delete(),
    ],
)]
class Slot extends Model
{
    use HasFactory;
    protected $table = 'slots';

    protected $fillable = [
        'name',
        'area_id',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
