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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ItemUriTemplateWithCollection;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Recipe as EntityRecipe;

#[Get(
    uriTemplate: '/item_uri_template_recipes/{id}{._format}',
    uriVariables: ['id'],
    provider: [self::class, 'provide'],
    openapi: false
)]
#[Get(
    uriTemplate: '/item_uri_template_recipes_state_option/{id}{._format}',
    uriVariables: ['id'],
    openapi: false,
    stateOptions: new Options(entityClass: EntityRecipe::class)
)]
class Recipe
{
    public ?string $id;
    public ?string $name = null;

    public ?string $description = null;

    public ?string $author = null;

    public ?array $recipeIngredient = [];

    public ?string $recipeInstructions = null;

    public ?string $prepTime = null;

    public ?string $cookTime = null;

    public ?string $totalTime = null;

    public ?string $recipeCuisine = null;

    public ?string $suitableForDiet = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $recipe = new self();
        $recipe->id = '1';
        $recipe->name = 'Dummy Recipe';
        $recipe->description = 'A simple recipe for testing purposes.';
        $recipe->prepTime = 'PT15M';
        $recipe->cookTime = 'PT30M';
        $recipe->totalTime = 'PT45M';
        $recipe->recipeIngredient = ['Ingredient 1', 'Ingredient 2'];
        $recipe->recipeInstructions = 'Do these things.';

        return $recipe;
    }
}
