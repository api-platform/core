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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy Offer.
 *
 * https://github.com/api-platform/core/issues/1107.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ApiResource]
#[ApiResource(uriTemplate: '/dummy_aggregate_offers/{id}/offers{._format}', uriVariables: ['id' => new Link(fromClass: DummyAggregateOffer::class, identifiers: ['id'], toProperty: 'aggregate')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/dummy_products/{id}/offers/{offers}/offers{._format}', uriVariables: ['id' => new Link(fromClass: DummyProduct::class, identifiers: ['id'], toProperty: 'product'), 'offers' => new Link(fromClass: DummyAggregateOffer::class, identifiers: ['id'], toProperty: 'aggregate')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/dummy_products/{id}/related_products/{relatedProducts}/offers/{offers}/offers{._format}', uriVariables: ['id' => new Link(fromClass: DummyProduct::class, identifiers: ['id']), 'relatedProducts' => new Link(fromClass: DummyProduct::class, identifiers: ['id'], toProperty: 'product'), 'offers' => new Link(fromClass: DummyAggregateOffer::class, identifiers: ['id'], toProperty: 'aggregate')], status: 200, operations: [new GetCollection()])]
#[ODM\Document]
class DummyOffer
{
    /**
     * @var int The id
     */
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private ?int $id = null;
    /**
     * @var int The dummy aggregate offer value
     */
    #[ODM\Field(type: 'int')]
    private ?int $value = null;
    /**
     * @var DummyAggregateOffer The dummy aggregate offer value
     */
    #[ODM\ReferenceOne(targetDocument: DummyAggregateOffer::class, inversedBy: 'offers', storeAs: 'id')]
    private ?DummyAggregateOffer $aggregate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function getAggregate(): ?DummyAggregateOffer
    {
        return $this->aggregate;
    }

    public function setAggregate(DummyAggregateOffer $aggregate): void
    {
        $this->aggregate = $aggregate;
    }
}
