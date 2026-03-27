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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ApiResource(
    rules: [
        'article' => ['required'],
        'content' => ['required'],
    ],
)]
class Issue7648Comment extends Model
{
    use HasFactory;

    protected $table = 'issue7648_comments';

    public function article(): BelongsTo
    {
        return $this->belongsTo(Issue7648Article::class, 'article_id');
    }
}
