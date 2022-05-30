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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy Offer.
 *
 * https://github.com/api-platform/core/issues/1107.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @ORM\Entity
 */
#[ApiResource]
#[ApiResource(uriTemplate: '/dummy_aggregate_offers/{id}/offers.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAggregateOffer::class, identifiers: ['id'], toProperty: 'aggregate')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/dummy_products/{id}/offers/{offers}/offers.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct::class, identifiers: ['id'], toProperty: 'product'), 'offers' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAggregateOffer::class, identifiers: ['id'], toProperty: 'aggregate')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/dummy_products/{id}/related_products/{relatedProducts}/offers/{offers}/offers.{_format}', uriVariables: ['id' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct::class, identifiers: ['id']), 'relatedProducts' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct::class, identifiers: ['id'], toProperty: 'product'), 'offers' => new Link(fromClass: \ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyAggregateOffer::class, identifiers: ['id'], toProperty: 'aggregate')], status: 200, operations: [new GetCollection()])]
class DummyOffer
{
    /**
     * @var int The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var int The dummy aggregate offer value
     *
     * @ORM\Column(type="integer")
     */
    private $value;
    /**
     * @var DummyAggregateOffer|null The dummy aggregate offer value
     *
     * @ORM\ManyToOne(targetEntity="DummyAggregateOffer", inversedBy="offers")
     */
    private $aggregate;

    public function getId()
    {
        return $this->id;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value)
    {
        $this->value = $value;
    }

    public function getAggregate()
    {
        return $this->aggregate;
    }

    public function setAggregate(DummyAggregateOffer $aggregate)
    {
        $this->aggregate = $aggregate;
    }
}
