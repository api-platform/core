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
        Schema::create('grand_fathers', function (Blueprint $table): void {
            $table->increments('id_grand_father');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('grand_sons', function (Blueprint $table): void {
            $table->increments('id_grand_son');
            $table->string('name');
            $table->unsignedInteger('grand_father_id')->nullable();
            $table->timestamps();
            $table->foreign('grand_father_id')->references('id_grand_father')->on('grand_fathers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grand_fathers');
        Schema::dropIfExists('grand_sons');
    }
};
