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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\ManagedRelation;

use Doctrine\ORM\Mapping as ORM;

/**
 * Carries no mapping attribute on purpose: the whole point of declaring the mapping in the
 * read direction, on the resource, is that the entity knows nothing about the presentation.
 */
#[ORM\Entity]
class ManagedRelationAuthor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    public string $name = '';

    public function getId(): ?int
    {
        return $this->id;
    }
}
