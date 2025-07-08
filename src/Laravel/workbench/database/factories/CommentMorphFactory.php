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

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\CommentMorph;

/**
 * @template TModel of \Workbench\App\Models\CommentMorph
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class CommentMorphFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = CommentMorph::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commentable_id' => PostWithMorphManyFactory::new(),
            'commentable_type' => PostWithMorphManyFactory::class,
            'content' => fake()->text(),
        ];
    }
}
