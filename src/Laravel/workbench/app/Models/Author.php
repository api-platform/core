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

use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\IsApiResource;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
// #[QueryParameter(key: 'name', filter: PartialSearchFilter::class)]
// #[QueryParameter(key: 'createdAt', schema: ['type' => 'date'], filter: DateFilter::class)]
// #[QueryParameter(key: 'updatedAt', schema: ['type' => 'date'], filter: DateFilter::class)]
#[QueryParameter(key: ':property', filter: PartialSearchFilter::class)]
class Author extends Model
{
    use HasFactory;
    use IsApiResource;
}
