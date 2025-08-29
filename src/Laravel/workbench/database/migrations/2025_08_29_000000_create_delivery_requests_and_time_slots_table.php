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
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pickup_time_slot_id')->nullable()->constrained('time_slots');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_requests');
        Schema::dropIfExists('time_slots');
    }
};
