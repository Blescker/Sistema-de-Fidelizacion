<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('ruc', 11)->nullable();
            $table->string('industry', 40)->default('otro');          // cafeteria, restaurante, retail...
            $table->string('currency', 3)->default('PEN');
            $table->string('currency_symbol', 5)->default('S/');
            $table->string('points_label', 30)->default('Puntos');    // Puntos / Estrellas / Sellos
            $table->string('timezone', 40)->default('America/Lima');
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->default('#4F46E5');
            $table->json('settings')->nullable();
            $table->string('status', 20)->default('active');          // active | suspended
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
