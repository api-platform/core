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

class CreatePatchDummyRelationTable extends Migration
{
    public function up(): void
    {
        Schema::create('patch_dummy_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(RelatedDummy::class, 'related_id')->nullable(true)->constrained();
        });
    }

    public function down(): void
    {
        Schema::drop('patch_dummy_relations');
    }
}
