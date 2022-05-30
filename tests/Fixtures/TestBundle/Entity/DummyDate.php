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

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy Date.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @ApiFilter (SearchFilter::class, properties={"dummyDate"})
 * @ApiFilter (DateFilter::class, properties={
 *     "dateIncludeNullAfter"=DateFilter::INCLUDE_NULL_AFTER,
 *     "dateIncludeNullBefore"=DateFilter::INCLUDE_NULL_BEFORE,
 *     "dateIncludeNullBeforeAndAfter"=DateFilter::INCLUDE_NULL_BEFORE_AND_AFTER
 * })
 * @ORM\Entity
 */
#[ApiResource(filters: ['my_dummy_date.date'])]
class DummyDate
{
    /**
     * @var int|null The id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var \DateTime The dummy date
     *
     * @ORM\Column(type="date")
     */
    public $dummyDate;
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    public $dateIncludeNullAfter;
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    public $dateIncludeNullBefore;
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
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
