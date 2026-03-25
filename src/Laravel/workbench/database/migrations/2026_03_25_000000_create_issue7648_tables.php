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
        Schema::create('issue7648_articles', static function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('issue7648_comments', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('issue7648_articles');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue7648_comments');
        Schema::dropIfExists('issue7648_articles');
    }
};
