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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Model that uses the casts() method instead of the $casts property.
 *
 * @see https://github.com/api-platform/core/issues/7662
 */
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
)]
class BookWithMethodCasts extends Model
{
    use HasUlids;

    protected $table = 'books';

    protected $visible = ['name', 'isbn', 'publication_date', 'is_available'];
    protected $fillable = ['name', 'isbn', 'publication_date', 'is_available'];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'publication_date' => 'datetime',
        ];
    }
}
