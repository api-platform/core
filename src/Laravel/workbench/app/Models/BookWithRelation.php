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
use ApiPlatform\Metadata\Get;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['book:read']]),
    ]
)]
#[ApiProperty(property: 'title', serialize: [new Groups(['book:read'])])]
class BookWithRelation extends Model
{
    use HasFactory;

    protected $table = 'book_with_relations';

    protected $fillable = [
        'title',
        'author_with_group_id',
    ];

    #[Groups(['book:read'])]
    public function authorWithGroup(): BelongsTo
    {
        return $this->belongsTo(AuthorWithGroup::class);
    }
}
