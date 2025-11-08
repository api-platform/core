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
class Recipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $author = null;

    #[ORM\Column(type: 'json', nullable: true)]
    public ?array $recipeIngredient = [];

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $recipeInstructions = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $prepTime = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $cookTime = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $totalTime = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $recipeCategory = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $recipeCuisine = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $suitableForDiet = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
