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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts_with_morph_many', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('comments_morph', function (Blueprint $table): void {
            $table->id();
            $table->text('content');
            $table->morphs('commentable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts_with_morph_many');
        Schema::dropIfExists('comments_morph');
    }
};
