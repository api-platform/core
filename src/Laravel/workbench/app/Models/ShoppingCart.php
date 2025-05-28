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
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(denormalizationContext: ['groups' => ['shopping_cart.write']], normalizationContext: ['groups' => ['shopping_cart.write']])]
#[Groups(['shopping_cart.write'])]
// We do not want to set the group on `cartItems` because it will lead to a circular reference
#[ApiProperty(serialize: new Groups(['cart_item.write']), property: 'user_identifier')]
#[ApiProperty(serialize: new Groups(['cart_item.write']), property: 'status')]
class ShoppingCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_identifier',
        'status',
    ];

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
