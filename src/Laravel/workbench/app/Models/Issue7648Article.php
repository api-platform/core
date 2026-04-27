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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ApiResource(
    rules: [
        'title' => ['required', 'max:255'],
        'content' => ['required'],
    ],
)]
class Issue7648Article extends Model
{
    use HasFactory;

    protected $table = 'issue7648_articles';

    public function comments(): HasMany
    {
        return $this->hasMany(Issue7648Comment::class, 'article_id');
    }
}
