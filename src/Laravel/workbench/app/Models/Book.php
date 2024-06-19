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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ApiResource(
    paginationEnabled: true,
    paginationItemsPerPage: 5,
)]
class Book extends Model
{
    use HasFactory;

    protected $visible = ['id', 'name', 'author'];
    protected $fillable = ['name'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
