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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\QueryParameterFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\SearchFilterValueTransformer;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\SearchTextAndDateFilter;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(openapi: false)]
#[GetCollection(
    uriTemplate: 'search_filter_parameter{._format}',
    parameters: [
        'foo' => new QueryParameter(filter: 'app_search_filter_via_parameter'),
        'fooAlias' => new QueryParameter(filter: 'app_search_filter_via_parameter', property: 'foo'),
        'order[:property]' => new QueryParameter(filter: 'app_search_filter_via_parameter.order_filter'),

        'searchPartial[:property]' => new QueryParameter(filter: 'app_search_filter_partial'),
        'searchExact[:property]' => new QueryParameter(filter: 'app_search_filter_with_exact'),
        'searchOnTextAndDate[:property]' => new QueryParameter(filter: 'app_filter_date_and_search'),
        'q' => new QueryParameter(property: 'hydra:freetextQuery'),
    ]
)]
#[QueryCollection(
    parameters: [
        'foo' => new QueryParameter(filter: 'app_search_filter_via_parameter'),
        'order[:property]' => new QueryParameter(filter: 'app_search_filter_via_parameter.order_filter'),

        'searchPartial[:property]' => new QueryParameter(filter: 'app_search_filter_partial'),
        'searchExact[:property]' => new QueryParameter(filter: 'app_search_filter_with_exact'),
        'searchOnTextAndDate[:property]' => new QueryParameter(filter: 'app_filter_date_and_search'),
        'q' => new QueryParameter(property: 'hydra:freetextQuery'),
    ]
)]
#[ApiFilter(SearchFilterValueTransformer::class, alias: 'app_search_filter_partial', properties: ['foo' => 'partial'], arguments: ['key' => 'searchPartial'])]
#[ApiFilter(SearchFilterValueTransformer::class, alias: 'app_search_filter_with_exact', properties: ['foo' => 'exact'], arguments: ['key' => 'searchExact'])]
#[ApiFilter(SearchTextAndDateFilter::class, alias: 'app_filter_date_and_search', properties: ['foo', 'createdAt'], arguments: ['dateFilterProperties' => ['createdAt' => 'exclude_null'], 'searchFilterProperties' => ['foo' => 'exact']])]
#[QueryParameter(key: ':property', filter: QueryParameterFilter::class)]
#[ORM\Entity]
class SearchFilterParameter
{
    /**
     * @var int The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    #[ORM\Column(type: 'string')]
    private string $foo = '';

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
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
