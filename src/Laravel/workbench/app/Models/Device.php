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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model with a custom primary key (device_id) that matches the foreign
 * key name on the related Port model. Used to test that ModelMetadata
 * does not incorrectly exclude the primary key from the attribute list.
 */
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ],
)]
class Device extends Model
{
    protected $primaryKey = 'device_id';
    protected $fillable = ['hostname'];

    public function ports(): HasMany
    {
        return $this->hasMany(Port::class, 'device_id', 'device_id');
    }
}
