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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/post_with_morph_manies/{id}/comments',
            uriVariables: [
                'id' => new Link(
                    fromProperty: 'comments',
                    fromClass: PostWithMorphMany::class,
                ),
            ]
        ),
        new Get(
            uriTemplate: '/post_with_morph_manies/{postId}/comments/{id}',
            uriVariables: [
                'postId' => new Link(
                    fromProperty: 'comments',
                    fromClass: PostWithMorphMany::class,
                ),
                'id' => new Link(
                    fromClass: CommentMorph::class,
                ),
            ]
        ),
    ]
)]
#[ApiProperty(identifier: true, serialize: new Groups(['comments']), property: 'id')]
#[ApiProperty(serialize: new Groups(['comments']), property: 'content')]
class CommentMorph extends Model
{
    protected $table = 'comments_morph';
    protected $fillable = ['content'];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
