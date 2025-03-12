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

use ApiPlatform\Metadata\Post;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Post(routePrefix: 'billing', uriTemplate: 'calculate', processor: [self::class, 'process'], status: 202)]
#[Post(routePrefix: 'shipping', uriTemplate: 'calculate', processor: [self::class, 'process'], status: 202)]
class PrefixedOperation extends Model
{
    use HasFactory;

    public static function process(): void
    {
    }
}
