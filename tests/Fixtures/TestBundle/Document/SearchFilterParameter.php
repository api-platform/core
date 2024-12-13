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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\ODMSearchFilterValueTransformer;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\ODMSearchTextAndDateFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\QueryParameterOdmFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[GetCollection(
    uriTemplate: 'search_filter_parameter{._format}',
    parameters: [
        'foo' => new QueryParameter(filter: 'app_odm_search_filter_via_parameter'),
        'fooAlias' => new QueryParameter(filter: 'app_odm_search_filter_via_parameter', property: 'foo'),
        'order[:property]' => new QueryParameter(filter: 'app_odm_search_filter_via_parameter.order_filter'),

        'searchPartial[:property]' => new QueryParameter(filter: 'app_odm_search_filter_partial'),
        'searchExact[:property]' => new QueryParameter(filter: 'app_odm_search_filter_with_exact'),
        'searchOnTextAndDate[:property]' => new QueryParameter(filter: 'app_odm_filter_date_and_search'),
        'q' => new QueryParameter(property: 'hydra:freetextQuery'),
    ]
)]
#[QueryCollection(
    parameters: [
        'foo' => new QueryParameter(filter: 'app_odm_search_filter_via_parameter'),
        'order[:property]' => new QueryParameter(filter: 'app_odm_search_filter_via_parameter.order_filter'),

        'searchPartial[:property]' => new QueryParameter(filter: 'app_odm_search_filter_partial'),
        'searchExact[:property]' => new QueryParameter(filter: 'app_odm_search_filter_with_exact'),
        'searchOnTextAndDate[:property]' => new QueryParameter(filter: 'app_odm_filter_date_and_search'),
        'q' => new QueryParameter(property: 'hydra:freetextQuery'),
    ]
)]
#[ApiFilter(ODMSearchFilterValueTransformer::class, alias: 'app_odm_search_filter_partial', properties: ['foo' => 'partial'], arguments: ['key' => 'searchPartial'])]
#[ApiFilter(ODMSearchFilterValueTransformer::class, alias: 'app_odm_search_filter_with_exact', properties: ['foo' => 'exact'], arguments: ['key' => 'searchExact'])]
#[ApiFilter(ODMSearchTextAndDateFilter::class, alias: 'app_odm_filter_date_and_search', properties: ['foo', 'createdAt'], arguments: ['dateFilterProperties' => ['createdAt' => 'exclude_null'], 'searchFilterProperties' => ['foo' => 'exact']])]
#[QueryParameter(key: ':property', filter: QueryParameterOdmFilter::class)]
#[ODM\Document]
class SearchFilterParameter
{
    /**
     * @var int The id
     */
    #[ODM\Field]
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    #[ODM\Field]
    private string $foo = '';
    #[ODM\Field(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFoo(): string
    {
        return $this->foo;
    }

    public function setFoo(string $foo): void
    {
        $this->foo = $foo;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
