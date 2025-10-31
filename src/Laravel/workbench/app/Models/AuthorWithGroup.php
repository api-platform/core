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
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource]
#[ApiProperty(property: 'name', serialize: [new Groups(['book:read'])])]
class AuthorWithGroup extends Model
{
    use HasFactory;

    protected $table = 'author_with_groups';

    protected $fillable = [
        'name',
    ];
}
