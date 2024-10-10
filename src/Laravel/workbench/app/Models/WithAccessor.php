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
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(normalizationContext: ['groups' => ['read']])]
class WithAccessor extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at', 'id'];

    #[ApiProperty(serialize: [new Groups(['read'])])]
    public function relation(): BelongsTo
    {
        return $this->belongsTo(WithAccessorRelation::class);
    }

    #[ApiProperty(serialize: [new Groups(['read'])])]
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => 'test',
        );
    }
}
