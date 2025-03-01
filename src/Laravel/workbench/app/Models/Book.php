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

use ApiPlatform\JsonApi\Filter\SparseFieldset;
use ApiPlatform\Laravel\Eloquent\Filter\BooleanFilter;
use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\JsonApi\SortFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\RangeFilter;
use ApiPlatform\Laravel\workbench\app\Enums\BookStatus;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Workbench\App\Http\Requests\BookFormRequest;

#[ApiResource(
    paginationEnabled: true,
    paginationItemsPerPage: 5,
    paginationClientItemsPerPage: true,
    rules: BookFormRequest::class,
    operations: [
        new Put(),
        new Patch(),
        new Get(),
        new Post(),
        new Delete(),
        new GetCollection(),
    ],
    graphQlOperations: [
        new Query(),
        new QueryCollection(
            parameters: [
                new QueryParameter(key: 'order[:property]', filter: OrderFilter::class),
            ],
        ),
        new QueryCollection(
            paginationItemsPerPage: 3,
            name: 'simplePagination',
            paginationType: 'page',
        ),
        new Mutation(name: 'create'),
    ]
)]
#[QueryParameter(key: 'isbn', schema: ['minimum' => '9783877138395', 'maximum' => '9793877138395'], filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'name', schema: ['pattern' => '/^a/'], filter: PartialSearchFilter::class)]
#[QueryParameter(key: 'author', openApi: new Parameter(name: 'author', in: 'query', allowEmptyValue: false), filter: EqualsFilter::class, required: true)]
#[QueryParameter(key: 'publicationDate', filter: DateFilter::class, property: 'publication_date')]
#[QueryParameter(key: 'publicationDateWithNulls', filter: DateFilter::class, property: 'publication_date', filterContext: ['include_nulls' => true])]
#[QueryParameter(key: 'isbn_range', filter: RangeFilter::class, property: 'isbn')]
#[QueryParameter(
    key: 'name2',
    filter: new OrFilter(new EqualsFilter()),
    property: 'name'
)]
#[QueryParameter(key: 'properties', filter: PropertyFilter::class)]
#[QueryParameter(key: 'published', filter: BooleanFilter::class)]
#[QueryParameter(key: 'fields', filter: SparseFieldset::class)]
#[QueryParameter(key: 'sort', filter: SortFilter::class)]
class Book extends Model
{
    use HasFactory;
    use HasUlids;

    protected $visible = ['name', 'author', 'isbn', 'status', 'publication_date', 'is_available', 'published'];
    protected $fillable = ['name', 'status', 'publication_date', 'isbn', 'is_available', 'published'];
    protected $casts = [
        'is_available' => 'boolean',
        'status' => BookStatus::class,
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
