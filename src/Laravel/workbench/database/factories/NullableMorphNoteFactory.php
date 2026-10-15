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
use Workbench\App\Models\NullableMorphNote;

class NullableMorphNoteFactory extends Factory
{
    protected $model = NullableMorphNote::class;

    public function definition(): array
    {
        return [
            'content' => $this->faker->sentence(),
        ];
    }
}
