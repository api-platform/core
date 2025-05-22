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

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Illuminate\Database\Eloquent\Model;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/issue_6932',
            rules: [
                'sur_name' => 'required',
            ]
        ),
    ],
)]
class Issue6932 extends Model
{
    protected $table = 'issue6932';
}
