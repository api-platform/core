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
use Workbench\App\Models\PostWithMorphMany;

/**
 * @template TModel of \Workbench\App\Models\PostWithMorphMany
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class PostWithMorphManyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = PostWithMorphMany::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(10),
            'content' => fake()->sentences(10, true),
        ];
    }
}
