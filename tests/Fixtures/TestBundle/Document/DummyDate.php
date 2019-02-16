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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\DateFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Dummy Date.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @ApiResource(attributes={
 *     "filters"={"my_dummy_date.mongodb.date"}
 * })
 * @ApiFilter(DateFilter::class, properties={
 *     "dateIncludeNullAfter"=DateFilter::INCLUDE_NULL_AFTER,
 *     "dateIncludeNullBefore"=DateFilter::INCLUDE_NULL_BEFORE,
 *     "dateIncludeNullBeforeAndAfter"=DateFilter::INCLUDE_NULL_BEFORE_AND_AFTER
 * })
 *
 * @ODM\Document
 */
class DummyDate
{
    /**
     * @var int The id
     *
     * @ODM\Id(strategy="INCREMENT", type="integer")
     */
    private $id;

    /**
     * @var \DateTime The dummy date
     *
     * @ODM\Field(type="date")
     */
    public $dummyDate;

    /**
     * @var \DateTime
     *
     * @ODM\Field(type="date")
     */
    public $dateIncludeNullAfter;

    /**
     * @var \DateTime
     *
     * @ODM\Field(type="date")
     */
    public $dateIncludeNullBefore;

    /**
     * @var \DateTime
     *
     * @ODM\Field(type="date")
     */
    public $dateIncludeNullBeforeAndAfter;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
