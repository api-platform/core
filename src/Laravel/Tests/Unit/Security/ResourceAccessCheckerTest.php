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

namespace ApiPlatform\Laravel\Tests\Unit\Security;

use ApiPlatform\Laravel\Eloquent\Paginator;
use ApiPlatform\Laravel\Security\ResourceAccessChecker;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\App\Models\Book;

class ResourceAccessCheckerTest extends TestCase
{
    use WithWorkbench;

    public function testNullObjectFallsBackToResourceClass(): void
    {
        Gate::shouldReceive('allows')
            ->once()
            ->with('import', Book::class)
            ->andReturn(true);

        $checker = new ResourceAccessChecker();
        $this->assertTrue($checker->isGranted(Book::class, 'import', ['object' => null]));
    }

    public function testPaginatorObjectFallsBackToResourceClass(): void
    {
        Gate::shouldReceive('allows')
            ->once()
            ->with('viewAny', Book::class)
            ->andReturn(true);

        $paginator = new Paginator(new LengthAwarePaginator([], 0, 1));
        $checker = new ResourceAccessChecker();
        $this->assertTrue($checker->isGranted(Book::class, 'viewAny', ['object' => $paginator]));
    }

    public function testActualObjectIsForwarded(): void
    {
        $book = new Book();

        Gate::shouldReceive('allows')
            ->once()
            ->with('view', $book)
            ->andReturn(true);

        $checker = new ResourceAccessChecker();
        $this->assertTrue($checker->isGranted(Book::class, 'view', ['object' => $book]));
    }
}
