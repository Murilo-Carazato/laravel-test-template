<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity')->nullable(); // Campo adicional do seu modelo
            $table->json('data')->nullable();     // Campo único para dados em vez de before/after
            
            // Campos para o relacionamento morphTo
            $table->string('model_type')->nullable();        // Renomeado de entity_type
            $table->unsignedBigInteger('model_id')->nullable(); // Renomeado de entity_id

            // Campos adicionais
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};