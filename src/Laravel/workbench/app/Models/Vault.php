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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ApiResource(
    operations: [
        new GetCollection(middleware: 'auth:sanctum'),
        new Post(
            middleware: 'auth:sanctum',
            policy: 'update',
            deserialize: false,
            provider: [self::class, 'provide'],
            read: true,
            write: false
        ),
    ]
)]
class Vault extends Model
{
    use HasFactory;

    public static function provide(): self
    {
        $v = new self();
        $v->id = 1;
        $v->secret = 'test';
        $v->created_at = new \DateTime();
        $v->updated_at = new \DateTime();

        return $v;
    }
}
