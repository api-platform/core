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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ApiResource(
    rules: [
        'title' => 'required',
    ]
)]
#[ApiProperty(property: 'title', types: ['https://schema.org/name'])]
class Post extends Model
{
    use HasFactory;

    /**
     * Get the comments for the blog post.
     */
    #[ApiProperty(uriTemplate: '/posts/{post}/comments{._format}')]
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
