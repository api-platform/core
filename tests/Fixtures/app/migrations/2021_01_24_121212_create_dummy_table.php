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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\RelatedDummy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use WouterJ\EloquentBundle\Facade\Schema;

class CreateDummyTable extends Migration
{
    public function up(): void
    {
        Schema::create('dummies', function (Blueprint $table) {
            $table->id()->nullable(true);
            $table->string('name');
            $table->string('alias')->nullable(true);
            $table->string('description')->nullable(true);
            $table->string('dummy')->nullable(true);
            $table->boolean('dummyBoolean')->nullable(true);
            $table->dateTime('dummyDate')->nullable(true);
            $table->float('dummyFloat')->nullable(true);
            $table->decimal('dummyPrice')->nullable(true);
            $table->foreignIdFor(RelatedDummy::class)->nullable(true)->constrained();
            $table->json('jsonData')->nullable(true);
            $table->json('arrayData')->nullable(true);
            $table->string('nameConverted')->nullable(true);
        });
    }

    public function down(): void
    {
        Schema::drop('dummies');
    }
}
