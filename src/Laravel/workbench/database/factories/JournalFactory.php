<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Symfony\Component\Uid\Ulid;
use Workbench\App\Models\Journal;

/**
 * @template TModel of \Workbench\App\Models\Journal
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class JournalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Journal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_name' => fake()->name(),
            'id' => (string) new Ulid(),
            'author_id' => AuthorFactory::new(),
            'title' => fake()->title(),
            'number' => fake()->numberBetween(1,30),
            'publication_date' => fake()->optional()->date(),
        ];
    }
}
