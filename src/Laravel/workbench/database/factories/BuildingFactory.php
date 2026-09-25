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
use Illuminate\Support\Str;
use Workbench\App\Models\Building;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Workbench\App\Models\Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'user_uuid' => (string) Str::uuid(),
        ];
    }
}
