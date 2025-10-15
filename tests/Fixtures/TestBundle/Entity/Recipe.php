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

#[ApiResource(types: ['https://schema.org/Recipe'], normalizationContext: ['hydra_prefix' => false])]
#[ORM\Entity]
class Recipe
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column]
    public string $name;

    #[ORM\Column(type: 'text')]
    public string $description;

    #[ORM\Column(nullable: true)]
    public ?string $cookTime = null;

    #[ORM\Column(nullable: true)]
    public ?string $prepTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
