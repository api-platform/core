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

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class BookStore
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column]
    public ?string $title = null;

    #[ORM\Column]
    public ?string $isbn = null;

    #[ORM\Column(nullable: true)]
    public ?string $description = null;

    #[ORM\Column(nullable: true)]
    public ?string $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
