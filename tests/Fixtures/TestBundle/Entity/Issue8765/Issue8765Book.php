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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8765;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Issue8765Book
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public string $title = '';

    #[ORM\ManyToOne(targetEntity: Issue8765Author::class)]
    public ?Issue8765Author $author = null;

    /** @var Collection<int, Issue8765Review> */
    #[ORM\OneToMany(targetEntity: Issue8765Review::class, mappedBy: 'book')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    public Collection $reviews;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
    }
}
