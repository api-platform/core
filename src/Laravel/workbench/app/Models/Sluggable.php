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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Get(
    uriVariables: [
        'slug',
    ],
)]
#[GetCollection]
class Sluggable extends Model
{
    use HasFactory;
    protected $hidden = ['id', 'created_at', 'updated_at'];

    public function getSlug(): string
    {
        return Str::slug($this->title);
    }
}
