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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\ThirdLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use WouterJ\EloquentBundle\Facade\Schema;

class CreateRelatedDummyTable extends Migration
{
    public function up(): void
    {
        Schema::create('related_dummies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(true);
            $table->string('symfony');
            $table->dateTime('dummyDate')->nullable(true);
            $table->boolean('dummyBoolean')->nullable(true);
            $table->integer('age')->nullable(true);
            $table->foreignIdFor(Dummy::class)->nullable(true)->constrained();
            $table->foreignIdFor(ThirdLevel::class)->nullable(true)->constrained();
        });
    }

    public function down(): void
    {
        Schema::drop('related_dummies');
    }
}
