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
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use WouterJ\EloquentBundle\Facade\Schema;

class CreateRelatedOwningDummyTable extends Migration
{
    public function up(): void
    {
        Schema::create('related_owning_dummies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Dummy::class)->constrained();
        });
    }

    public function down(): void
    {
        Schema::drop('related_owning_dummies');
    }
}
