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
use Doctrine\ORM\Mapping as ORM;

/**
 * Dummy Immutable Date.
 */
#[ApiResource(filters: ['my_dummy_immutable_date.date'])]
#[ORM\Entity]
class DummyImmutableDate
{
    /**
     * @var int|null The id
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;
    /**
     * @var \DateTimeImmutable The dummy date
     */
    #[ORM\Column(type: 'date_immutable')]
    public $dummyDate;

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}
