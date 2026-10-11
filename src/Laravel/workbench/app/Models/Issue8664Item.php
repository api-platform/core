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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ApiResource]
class Issue8664Item extends Model
{
    protected $table = 'issue8664_items';

    protected $fillable = ['code'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Issue8664Category::class);
    }
}
