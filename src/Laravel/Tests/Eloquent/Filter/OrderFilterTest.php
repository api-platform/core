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

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use Workbench\Database\Factories\ActiveBookFactory;

class OrderFilterTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use RefreshDatabase;
    use WithWorkbench;

    public function testQueryParameterWithCamelCaseProperty(): void
    {
        ActiveBookFactory::new(['is_active' => true])->count(2)->create();
        ActiveBookFactory::new(['is_active' => false])->count(3)->create();

        DB::enableQueryLog();
        $response = $this->get('/api/active_books?sort[isActive]=asc', ['Accept' => ['application/ld+json']]);
        $response->assertStatus(200);
        $this->assertEquals(\DB::getQueryLog()[1]['query'], 'select * from "active_books" order by "isActive" asc limit 30 offset 0');
        DB::flushQueryLog();
        $response = $this->get('/api/active_books?sort[isActive]=desc', ['Accept' => ['application/ld+json']]);
        $response->assertStatus(200);
        $this->assertEquals(DB::getQueryLog()[1]['query'], 'select * from "active_books" order by "isActive" desc limit 30 offset 0');
    }
}
