<?php

namespace Workbench\App\Models;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\RangeFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ApiResource]
#[QueryParameter(key: 'journalName', schema: ['pattern' => '/^a/'], filter: PartialSearchFilter::class, property: 'journal_name')]
#[QueryParameter(key: 'journalNameLengthValidation', schema: ['minLength' => '3'], filter: PartialSearchFilter::class, property: 'journal_name')]
#[QueryParameter(key: 'number', schema: ['exclusiveMinimum' => '0', 'maximum' => '30'], filter: RangeFilter::class)]
#[QueryParameter(key: 'author', openApi: new Parameter(name: 'author', in: 'query', allowEmptyValue: true), filter: EqualsFilter::class, required: true)]
#[QueryParameter(
    key: 'name2',
    filter: new OrFilter(new EqualsFilter()),
    property: 'name',
    schema: ['minItems' => 2]
)]
class Journal extends Model
{
    use HasFactory;
    use HasUlids;

    protected $visible = ['journal_name', 'title', 'number', 'publication_date'];
    protected $fillable = ['journal_name'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
