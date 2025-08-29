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

use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\IsApiResource;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Serializer\Attribute\Groups;

#[QueryParameter(key: ':property', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'createdAt', filter: DateFilter::class)]
#[QueryParameter(key: 'order[:property]', filter: OrderFilter::class)]
#[ApiProperty(property: 'name', serialize: [new Groups('read')])]
class Author extends Model
{
    use HasFactory;
    use IsApiResource;
}
