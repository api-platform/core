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
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Recipe as EntityRecipe;

#[GetCollection(
    uriTemplate: '/item_uri_template_recipes{._format}',
    provider: [self::class, 'provide'],
    openapi: false,
    shortName: 'CollectionRecipe',
    itemUriTemplate: '/item_uri_template_recipes/{id}{._format}',
    normalizationContext: ['hydra_prefix' => false],
)]
#[GetCollection(
    uriTemplate: '/item_uri_template_recipes_state_option{._format}',
    openapi: false,
    shortName: 'CollectionRecipe',
    itemUriTemplate: '/item_uri_template_recipes_state_option/{id}{._format}',
    stateOptions: new Options(entityClass: EntityRecipe::class),
    normalizationContext: ['hydra_prefix' => false],
)]
class RecipeCollection
{
    public ?string $id;
    public ?string $name = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $recipe = new self();
        $recipe->id = '1';
        $recipe->name = 'Dummy Recipe';

        $recipe2 = new self();
        $recipe2->id = '2';
        $recipe2->name = 'Dummy Recipe 2';

        return [$recipe, $recipe2];
    }
}
