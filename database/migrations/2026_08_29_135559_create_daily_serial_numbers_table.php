<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('vehicle_type', ['roda23', 'roda4', 'rodaplus4']);
            $table->string('serial_start');
            $table->foreignId('inputted_by')->constrained('users');
            $table->dateTime('inputted_at');
            $table->unique(['date', 'vehicle_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_serial_numbers');
    }
};
