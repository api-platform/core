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
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post as PostOperation;
use ApiPlatform\Metadata\Put;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/posts/{post}/comments/{id}{._format}',
            uriVariables: [
                'post' => new Link(fromClass: Post::class, toProperty: 'post'),
                'id' => new Link(fromClass: Comment::class),
            ],
        ),
        new GetCollection(
            uriTemplate: '/posts/{post}/comments{._format}',
            uriVariables: ['post' => new Link(fromClass: Post::class, toProperty: 'post')],
        ),
        new GetCollection(
            uriTemplate: '/posts_reverse/{post}/comments{._format}',
            uriVariables: ['post' => new Link(fromClass: Post::class, fromProperty: 'comments')],
        ),
        new PostOperation(),
        new Put(),
        new Patch(),
    ]
)]
class Comment extends Model
{
    use HasFactory;

    protected $hidden = ['internal_note'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
