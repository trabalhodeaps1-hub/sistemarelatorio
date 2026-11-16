<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('equipamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            $table->string('tag', 40)->unique();
            $table->string('nome');
            $table->string('criticidade', 20);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('motivos_reprovacao', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->boolean('exige_comentario')->default(false);
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('equipes', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('especialidade')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('equipe_membros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipe_id')->constrained('equipes')->cascadeOnDelete();
            $table->string('nome');
            $table->string('matricula', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('sla_prazos', function (Blueprint $table) {
            $table->id();
            $table->string('criticidade', 20)->unique();
            $table->unsignedInteger('horas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_prazos');
        Schema::dropIfExists('equipe_membros');
        Schema::dropIfExists('equipes');
        Schema::dropIfExists('motivos_reprovacao');
        Schema::dropIfExists('equipamentos');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('areas');
    }
};
