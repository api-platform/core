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

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\Database\Factories\AuthorFactory;
use Workbench\Database\Factories\BookFactory;
use Workbench\Database\Factories\CommentFactory;
use Workbench\Database\Factories\JournalFactory;
use Workbench\Database\Factories\PostFactory;
use Workbench\Database\Factories\SluggableFactory;
use Workbench\Database\Factories\UserFactory;
use Workbench\Database\Factories\VaultFactory;
use Workbench\Database\Factories\WithAccessorFactory;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BookFactory::new()->has(AuthorFactory::new())->count(10)->create();
        JournalFactory::new()->has(AuthorFactory::new())->count(10)->create();
        PostFactory::new()->has(CommentFactory::new()->count(10))->count(10)->create();
        SluggableFactory::new()->count(10)->create();
        UserFactory::new()->create();
        WithAccessorFactory::new()->create();
        VaultFactory::new()->count(10)->create();
    }
}
