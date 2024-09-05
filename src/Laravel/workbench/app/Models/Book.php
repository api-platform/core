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

use ApiPlatform\Laravel\Eloquent\Filter\AfterDateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\BeforeDateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\RangeFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workbench\App\Http\Requests\BookFormRequest;

#[ApiResource(
    paginationEnabled: true,
    paginationItemsPerPage: 5,
    rules: BookFormRequest::class,
    operations: [
        new Put(),
        new Patch(),
        new Get(),
        new Post(),
        new Delete(),
        new GetCollection(),
    ]
)]
#[QueryParameter(key: 'isbn', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'name', filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'publicationDate', schema: ['type' => 'date'], filter: DateFilter::class)]
#[QueryParameter(key: 'publicationDate[before]', schema: ['type' => 'date'], filter: BeforeDateFilter::class, property: 'publication_date')]
#[QueryParameter(
    key: 'publicationDate[after]',
    schema: ['type' => 'date'],
    filter: AfterDateFilter::class,
    property: 'publication_date',
    filterContext: ['nulls_comparison' => 'include_null_before_and_after']
)]
#[QueryParameter(key: 'isbn_range', schema: ['type' => 'string'], filter: RangeFilter::class, property: 'isbn', description: 'Syntax: \<lt\>.\<valueToCompareTo\> You can use lt, gt, lte, gte or between (to do it, add: .\<value\> at the end)')]
#[QueryParameter(key: 'order', schema: ['type' => 'string'], filter: OrderFilter::class, description: 'Syntax: \<propertyToOrderOn\>.\<asc|desc\>', filterContext: ['nulls_comparison' => 'nulls_smallest'])]
#[QueryParameter(
    key: 'name2',
    schema: ['type' => 'array', 'items' => ['type' => 'string']],
    openApi: new Parameter(name: 'name2[]', in: 'query', style: 'deepObject', explode: true),
    filter: new OrFilter(new PartialSearchFilter()),
    property: 'name'
)]
class Book extends Model
{
    use HasFactory;
    use HasUlids;

    protected $visible = ['name', 'author', 'isbn', 'publication_date'];
    protected $fillable = ['name'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
