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
final class FilterWithStateOptions
{
    public function __construct(public readonly string $id, public readonly \DateTimeImmutable $dummyDate, public readonly string $name)
    {
    }
}
