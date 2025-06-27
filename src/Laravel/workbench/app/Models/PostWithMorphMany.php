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
use ApiPlatform\Metadata\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [
    new Post(
        denormalizationContext: ['groups' => ['comments']],
        normalizationContext: ['groups' => ['comments']],
    ),
])]
#[ApiProperty(serialize: new Groups(['comments']), property: 'title')]
#[ApiProperty(serialize: new Groups(['comments']), property: 'content')]
#[ApiProperty(serialize: new Groups(['comments']), property: 'comments')]
class PostWithMorphMany extends Model
{
    protected $table = 'posts_with_morph_many';
    protected $fillable = ['title', 'content'];

    public function comments(): MorphMany
    {
        return $this->morphMany(CommentMorph::class, 'commentable');
    }
}
