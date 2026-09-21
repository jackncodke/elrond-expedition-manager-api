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
        Schema::create('expedicoes', function (Blueprint $table) {
            $table->id();
            $table->string('protocolo', 30)->unique();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('status', 20)->default('PENDENTE');
            $table->text('motivo_rejeicao')->nullable();
            $table->foreignId('decidido_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('decidido_em')->nullable();
            $table->timestamps();

            $table->index('protocolo');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedicoes');
    }
};
