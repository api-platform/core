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

#[ORM\Entity]
class ManagedRelationBook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    public string $title = '';

    #[ORM\ManyToOne(targetEntity: ManagedRelationAuthor::class)]
    public ?ManagedRelationAuthor $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
