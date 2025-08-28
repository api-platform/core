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
use ApiPlatform\Metadata\Post;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            normalizationContext: [
                'groups' => [
                    'delivery_request:read',
                ],
            ],
            denormalizationContext: [
                'groups' => [
                    'delivery_request:write',
                ],
            ]
        ),
    ]
)]
#[ApiProperty(property: 'pickupTimeSlot', serialize: new Groups(['delivery_request:read', 'delivery_request:write']))]
#[ApiProperty(property: 'note', serialize: new Groups(['delivery_request:read', 'delivery_request:write']))]
class DeliveryRequest extends Model
{
    use HasFactory;

    public function pickupTimeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'pickup_time_slot_id');
    }
}
