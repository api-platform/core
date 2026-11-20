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

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765MappedReview;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[ORM\Entity]
#[Map(target: Issue8765MappedReview::class)]
class Issue8765Review
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public int $rating = 0;

    #[ORM\ManyToOne(targetEntity: Issue8765Book::class, inversedBy: 'reviews')]
    #[Map(if: false)]
    public ?Issue8765Book $book = null;
}
