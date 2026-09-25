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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/buildings', name: 'api_buildings_get_collection'),
        new Get(uriTemplate: '/buildings/{uuid}', name: 'api_buildings_get_item'),
        new Post(uriTemplate: '/buildings', name: 'api_buildings_post'),
        new Put(uriTemplate: '/buildings/{uuid}', name: 'api_buildings_put'),
        new Delete(uriTemplate: '/buildings/{uuid}', name: 'api_buildings_delete'),
    ],
    normalizationContext: ['groups' => ['building:read']],
    denormalizationContext: ['groups' => ['building:write']],
    paginationEnabled: true,
)]
#[ApiProperty(property: 'name', serialize: [new Groups(['building:read', 'building:write'])])]
#[ApiProperty(property: 'user_uuid', serialize: [new Groups(['building:read', 'building:write'])])]
class Building extends Model
{
    use HasFactory;

    protected $primaryKey = 'uuid';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['name', 'user_uuid'];

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
