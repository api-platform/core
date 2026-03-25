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
use Workbench\App\Models\Issue7648Article;

/**
 * @template TModel of Issue7648Article
 *
 * @extends Factory<TModel>
 */
class Issue7648ArticleFactory extends Factory
{
    /** @var class-string<TModel> */
    protected $model = Issue7648Article::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
        ];
    }
}
