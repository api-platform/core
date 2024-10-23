<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FilterWithStateOptionsEntity;

#[GetCollection(
    uriTemplate: 'filter_with_state_options',
    stateOptions: new Options(entityClass: FilterWithStateOptionsEntity::class),
    parameters: ['date' => new QueryParameter(filter: 'filter_with_state_options_date', property: 'dummyDate')],
    provider: CollectionProvider::class
)]
#[ApiFilter(DateFilter::class, alias: 'filter_with_state_options_date', properties: ['dummyDate' => DateFilter::EXCLUDE_NULL])]
final readonly class FilterWithStateOptions
{
    public function __construct(public string $id, public \DateImmutable $dummyDate, public string $name) {}
}
