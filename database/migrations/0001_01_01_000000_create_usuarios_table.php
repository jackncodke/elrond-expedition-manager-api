<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->string('nome', 120);
            $table->string('codigo', 20)->nullable()->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
