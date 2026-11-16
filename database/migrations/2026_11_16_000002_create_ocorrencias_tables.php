<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocorrencias', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->nullable()->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            $table->foreignId('equipamento_id')->nullable()->constrained('equipamentos')->restrictOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('titulo');
            $table->text('descricao');
            $table->string('local')->nullable();
            $table->string('criticidade_sugerida', 20);
            $table->string('criticidade', 20);
            $table->boolean('risco_pessoas')->default(false);
            $table->boolean('equipamento_parado')->default(false);
            $table->text('observacoes')->nullable();
            $table->string('status', 20)->default('pendente');
            $table->unsignedInteger('reenvios')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'criticidade']);
        });

        Schema::create('ocorrencia_analises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ocorrencia_id')->constrained('ocorrencias')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('acao', 20);
            $table->foreignId('motivo_reprovacao_id')->nullable()->constrained('motivos_reprovacao')->restrictOnDelete();
            $table->text('comentario')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('ordens_servico', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->nullable()->unique();
            $table->foreignId('ocorrencia_id')->unique()->constrained('ocorrencias')->restrictOnDelete();
            $table->string('status', 20)->default('aberta');
            $table->string('criticidade', 20);
            $table->foreignId('equipe_id')->nullable()->constrained('equipes')->restrictOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('prazo_previsto')->nullable();
            $table->timestamp('iniciada_em')->nullable();
            $table->timestamp('concluida_em')->nullable();
            $table->timestamp('cancelada_em')->nullable();
            $table->timestamp('atraso_notificado_em')->nullable();
            $table->text('observacao_conclusao')->nullable();
            $table->timestamps();

            $table->index(['status', 'prazo_previsto']);
        });

        Schema::create('fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ocorrencia_id')->constrained('ocorrencias')->cascadeOnDelete();
            $table->string('tipo', 10)->default('antes');
            $table->string('path');
            $table->unsignedInteger('tamanho')->default(0);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['ocorrencia_id', 'tipo']);
        });

        Schema::create('os_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('status_anterior', 20)->nullable();
            $table->string('status_novo', 20);
            $table->text('comentario')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 30);
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('dados')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('os_historico');
        Schema::dropIfExists('fotos');
        Schema::dropIfExists('ordens_servico');
        Schema::dropIfExists('ocorrencia_analises');
        Schema::dropIfExists('ocorrencias');
    }
};
