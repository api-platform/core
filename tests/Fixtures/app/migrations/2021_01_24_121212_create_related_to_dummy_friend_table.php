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

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\DummyFriend;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\RelatedDummy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use WouterJ\EloquentBundle\Facade\Schema;

class CreateRelatedToDummyFriendTable extends Migration
{
    public function up(): void
    {
        Schema::create('related_to_dummy_friends', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable(true);
            $table->foreignIdFor(DummyFriend::class)->constrained();
            $table->foreignIdFor(RelatedDummy::class)->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::drop('related_to_dummy_friends');
    }
}
