<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_serial_numbers', function (Blueprint $table) {
            $table->string('serial_start', 20)->change();
        });
    }

    public function down(): void
    {
        Schema::table('daily_serial_numbers', function (Blueprint $table) {
            $table->integer('serial_start')->change();
        });
    }
};
