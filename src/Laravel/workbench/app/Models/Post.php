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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post as PostOperation;
use ApiPlatform\Metadata\Put;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[ApiResource(
    uriVariables: [
        'slug',
    ],
    operations: [
        new Get(), new Put(), new Patch(), new Delete(),
    ],
    rules: [
        'title' => 'required',
    ]
)]
#[ApiResource(
    operations: [
        new GetCollection(), new PostOperation(),
    ]
)]
#[ApiProperty(property: 'title', types: ['https://schema.org/name'])]
class Post extends Model
{
    use HasFactory;

    public function getSlug(): string
    {
        return Str::slug($this->title);
    }

    /**
     * Get the comments for the blog post.
     */
    #[ApiProperty(uriTemplate: '/posts/{post}/comments{._format}')]
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
