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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['nullable_morph_note:read']],
        ),
        new Post(
            normalizationContext: ['groups' => ['nullable_morph_note:read']],
            denormalizationContext: ['groups' => ['nullable_morph_note:write']],
        ),
        new Patch(
            normalizationContext: ['groups' => ['nullable_morph_note:read']],
            denormalizationContext: ['groups' => ['nullable_morph_note:write']],
        ),
    ]
)]
#[ApiProperty(property: 'noteable', serialize: new Groups(['nullable_morph_note:read', 'nullable_morph_note:write']))]
#[ApiProperty(property: 'content', serialize: new Groups(['nullable_morph_note:read', 'nullable_morph_note:write']))]
class NullableMorphNote extends Model
{
    use HasFactory;

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }
}
