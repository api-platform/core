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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Document;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy Offer.
 * https://github.com/api-platform/core/issues/1107.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @ApiResource
 * @ODM\Document
 */
class DummyOffer
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var int The dummy aggregate offer value
     *
     * @ODM\Field(type="integer")
     */
    private $value;

    /**
     * @var DummyAggregateOffer The dummy aggregate offer value
     *
     * @ODM\ReferenceOne(targetDocument=DummyAggregateOffer::class, inversedBy="offers", storeAs="id")
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
