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

class CreateEmbeddableDummyTable extends Migration
{
    public function up(): void
    {
        Schema::create('embeddable_dummies', function (Blueprint $table) {
            $table->id();
            $table->string('dummyName')->nullable(true);
            $table->boolean('dummyBoolean')->nullable(true);
            $table->dateTime('dummyDate')->nullable(true);
            $table->float('dummyFloat')->nullable(true);
            $table->decimal('dummyPrice')->nullable(true);
            $table->string('symfony')->nullable(true);
            $table->foreignIdFor(RelatedDummy::class)->constrained();
        });
    }

    public function down(): void
    {
        Schema::drop('embeddable_dummies');
    }
}
