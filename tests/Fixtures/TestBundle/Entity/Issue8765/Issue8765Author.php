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

use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue8765\Issue8765MappedAuthor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Transform\MapCollection;

#[ORM\Entity]
#[Map(target: Issue8765MappedAuthor::class)]
class Issue8765Author
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public string $name = '';

    #[ORM\ManyToOne(targetEntity: Issue8765Country::class)]
    public ?Issue8765Country $country = null;

    /** @var Collection<int, Issue8765Award> */
    #[ORM\OneToMany(targetEntity: Issue8765Award::class, mappedBy: 'author')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    #[Map(transform: new MapCollection())]
    public Collection $awards;

    public function __construct()
    {
        $this->awards = new ArrayCollection();
    }
}
