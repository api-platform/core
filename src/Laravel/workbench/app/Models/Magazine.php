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
use ApiPlatform\Metadata\Post;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[ApiResource(
    shortName: 'PublicMagazine',
    operations: [
        new GetCollection(uriTemplate: '/public_magazines', name: 'api_public_magazines_get_collection'),
        new Get(uriTemplate: '/public_magazines/{uuid}', name: 'api_public_magazines_get_item'),
        new Post(uriTemplate: '/public_magazines', name: 'api_public_magazines_post'),
    ],
)]
#[ApiResource(
    shortName: 'ArchivedMagazine',
    operations: [
        new GetCollection(uriTemplate: '/archived_magazines', name: 'api_archived_magazines_get_collection'),
        new Get(uriTemplate: '/archived_magazines/{uuid}', name: 'api_archived_magazines_get_item'),
    ],
)]
class Magazine extends Model
{
    use HasFactory;

    protected $primaryKey = 'uuid';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['name'];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(static function (self $model): void {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
