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

use ApiPlatform\Laravel\workbench\app\Enums\BookStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Symfony\Component\Uid\Ulid;
use Workbench\App\Models\Book;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'id' => (string) new Ulid(),
            'author_id' => AuthorFactory::new(),
            'isbn' => fake()->isbn13(),
            'publication_date' => fake()->optional()->date(),
            'is_available' => 1 === random_int(0, 1),
            'status' => BookStatus::PUBLISHED,
            'internal_note' => fake()->text(),
            'published' => fake()->boolean(100),
        ];
    }
}
