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

namespace ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy Date.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
#[ApiFilter(DateFilter::class, properties: [
    'dateIncludeNullAfter' => DateFilter::INCLUDE_NULL_AFTER,
    'dateIncludeNullBefore' => DateFilter::INCLUDE_NULL_BEFORE,
    'dateIncludeNullBeforeAndAfter' => DateFilter::INCLUDE_NULL_BEFORE_AND_AFTER,
])]
#[ApiFilter(SearchFilter::class, properties: ['dummyDate'])]
#[ApiResource(filters: ['my_dummy_date.date'])]
#[ORM\Entity]
class DummyDate
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    /**
     * @var \DateTime The dummy date
     */
    #[ORM\Column(type: 'date')]
    public $dummyDate;
    /**
     * @var \DateTime|null
     */
    #[ORM\Column(type: 'date', nullable: true)]
    public $dateIncludeNullAfter;
    /**
     * @var \DateTime|null
     */
    #[ORM\Column(type: 'date', nullable: true)]
    public $dateIncludeNullBefore;
    /**
     * @var \DateTime|null
     */
    #[ORM\Column(type: 'date', nullable: true)]
    public $dateIncludeNullBeforeAndAfter;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}
