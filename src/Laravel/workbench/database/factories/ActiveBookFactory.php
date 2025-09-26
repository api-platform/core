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
use Workbench\App\Models\ActiveBook;

class ActiveBookFactory extends Factory
{
    protected $model = ActiveBook::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(),
        ];
    }
}
