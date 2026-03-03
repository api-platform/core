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

/*
 * Tables for testing custom primary key handling in ModelMetadata.
 *
 * Uses the common convention of <table>_id as primary key, where the
 * PK name on the parent table matches the FK name on the child table.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('devices', static function (Blueprint $table): void {
            $table->increments('device_id');
            $table->string('hostname');
            $table->timestamps();
        });

        Schema::create('ports', static function (Blueprint $table): void {
            $table->increments('port_id');
            $table->unsignedInteger('device_id');
            $table->string('name');
            $table->foreign('device_id')->references('device_id')->on('devices');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ports');
        Schema::dropIfExists('devices');
    }
};
