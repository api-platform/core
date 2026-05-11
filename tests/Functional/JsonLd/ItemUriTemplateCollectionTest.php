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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ItemUriTemplateWithCollection\Recipe;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ItemUriTemplateWithCollection\RecipeCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Recipe as EntityRecipe;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class ItemUriTemplateCollectionTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Recipe::class, RecipeCollection::class];
    }

    public function testCollectionItemsExposeItemUriTemplateAsId(): void
    {
        self::createClient()->request('GET', '/item_uri_template_recipes');
        $this->assertResponseIsSuccessful();

        $this->assertJsonContains([
            'member' => [
                [
                    '@type' => 'Recipe',
                    '@id' => '/item_uri_template_recipes/1',
                    'name' => 'Dummy Recipe',
                ],
                [
                    '@type' => 'Recipe',
                    '@id' => '/item_uri_template_recipes/2',
                    'name' => 'Dummy Recipe 2',
                ],
            ],
        ]);
    }

    public function testItemUriTemplateAppliesWhenSourceIsStateOption(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema([EntityRecipe::class]);

        $manager = $this->getManager();
        for ($i = 0; $i < 10; ++$i) {
            $recipe = new EntityRecipe();
            $recipe->name = "Recipe $i";
            $recipe->description = "Description of recipe $i";
            $recipe->author = "Author $i";
            $recipe->recipeIngredient = [
                "Ingredient 1 for recipe $i",
                "Ingredient 2 for recipe $i",
            ];
            $recipe->recipeInstructions = "Instructions for recipe $i";
            $recipe->prepTime = '10 minutes';
            $recipe->cookTime = '20 minutes';
            $recipe->totalTime = '30 minutes';
            $recipe->recipeCategory = "Category $i";
            $recipe->recipeCuisine = "Cuisine $i";
            $recipe->suitableForDiet = "Diet $i";

            $manager->persist($recipe);
        }
        $manager->flush();

        self::createClient()->request('GET', '/item_uri_template_recipes_state_option');
        $this->assertResponseIsSuccessful();

        $this->assertJsonContains([
            'member' => [
                [
                    '@type' => 'Recipe',
                    '@id' => '/item_uri_template_recipes_state_option/1',
                    'name' => 'Recipe 0',
                ],
                [
                    '@type' => 'Recipe',
                    '@id' => '/item_uri_template_recipes_state_option/2',
                    'name' => 'Recipe 1',
                ],
                [
                    '@type' => 'Recipe',
                    '@id' => '/item_uri_template_recipes_state_option/3',
                    'name' => 'Recipe 2',
                ],
            ],
        ]);
    }
}
