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

class CreateRelationEmbedderTable extends Migration
{
    public function up(): void
    {
        Schema::create('relation_embedders', function (Blueprint $table) {
            $table->id();
            $table->string('paris');
            $table->string('krondstadt');
            $table->foreignIdFor(RelatedDummy::class, 'related_id')->nullable(true)->constrained();
            $table->foreignIdFor(RelatedDummy::class, 'another_related_id')->nullable(true)->constrained();
        });
    }

    public function down(): void
    {
        Schema::drop('relation_embedders');
    }
}
