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
use ApiPlatform\Metadata\NotExposed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Serializer\Attribute\Groups;

#[NotExposed]
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
