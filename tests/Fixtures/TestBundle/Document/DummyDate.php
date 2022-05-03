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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Doctrine\Odm\Filter\DateFilter;
use ApiPlatform\Doctrine\Odm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy Date.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @ApiFilter (SearchFilter::class, properties={"dummyDate"})
 * @ApiFilter (DateFilter::class, properties={
 *     "dateIncludeNullAfter"=DateFilter::INCLUDE_NULL_AFTER,
 *     "dateIncludeNullBefore"=DateFilter::INCLUDE_NULL_BEFORE,
 *     "dateIncludeNullBeforeAndAfter"=DateFilter::INCLUDE_NULL_BEFORE_AND_AFTER
 * })
 * @ODM\Document
 */
#[ApiResource(filters: ['my_dummy_date.mongodb.date'])]
class DummyDate
{
    /**
     * @var int|null The id
     *
     * @ODM\Id(strategy="INCREMENT", type="int")
     */
    private ?int $id = null;
    /**
     * @var \DateTime|null The dummy date
     *
     * @ODM\Field(type="date")
     */
    public $dummyDate;
    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    public $dateIncludeNullAfter;
    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    public $dateIncludeNullBefore;
    /**
     * @var \DateTime|null
     *
     * @ODM\Field(type="date")
     */
    public $dateIncludeNullBeforeAndAfter;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}
