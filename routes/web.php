<?php

use App\Http\Controllers\AnaliseController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Cadastros\AreaController;
use App\Http\Controllers\Cadastros\CategoriaController;
use App\Http\Controllers\Cadastros\EquipamentoController;
use App\Http\Controllers\Cadastros\EquipeController;
use App\Http\Controllers\Cadastros\MotivoReprovacaoController;
use App\Http\Controllers\Cadastros\SlaPrazoController;
use App\Http\Controllers\Cadastros\UsuarioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FotoController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\OcorrenciaController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RelatorioController;
use Illuminate\Support\Facades\Route;

// ---------- Visitantes ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

// ---------- Autenticados ----------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil/senha', [PerfilController::class, 'updateSenha'])->name('perfil.senha');

    // Notificações (sino)
    Route::prefix('notificacoes')->name('notificacoes.')->controller(NotificacaoController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/contador', 'contador')->name('contador');
        Route::post('/ler-todas', 'lerTodas')->name('ler-todas');
        Route::get('/{id}/abrir', 'abrir')->name('abrir');
        Route::post('/{id}/ler', 'ler')->name('ler');
    });

    // Select dependente (formulário de ocorrência, filtros de relatório)
    Route::get('/areas/{area}/equipamentos', [AreaController::class, 'equipamentos'])->name('areas.equipamentos');

    // Fotos (disco privado, protegidas por FotoPolicy)
    Route::get('/fotos/{foto}', [FotoController::class, 'show'])->name('fotos.show');

    // ---------- Ocorrências ----------
    Route::middleware('role:tecnico')->group(function () {
        Route::get('/ocorrencias', [OcorrenciaController::class, 'index'])->name('ocorrencias.index');
        Route::get('/ocorrencias/nova', [OcorrenciaController::class, 'create'])->name('ocorrencias.create');
        Route::post('/ocorrencias', [OcorrenciaController::class, 'store'])->name('ocorrencias.store');
        Route::get('/ocorrencias/{ocorrencia}/editar', [OcorrenciaController::class, 'edit'])->name('ocorrencias.edit');
        Route::put('/ocorrencias/{ocorrencia}', [OcorrenciaController::class, 'update'])->name('ocorrencias.update');
        Route::patch('/ocorrencias/{ocorrencia}/cancelar', [OcorrenciaController::class, 'cancelar'])->name('ocorrencias.cancelar');
    });
    // Detalhe: dono, supervisor, gerente e master (OcorrenciaPolicy@view)
    Route::get('/ocorrencias/{ocorrencia}', [OcorrenciaController::class, 'show'])->name('ocorrencias.show');

    // ---------- Análise (Supervisor) ----------
    Route::middleware('role:supervisor')->prefix('analises')->name('analises.')->group(function () {
        Route::get('/', [AnaliseController::class, 'index'])->name('index');
        Route::get('/{ocorrencia}', [AnaliseController::class, 'show'])->name('show');
        Route::post('/{ocorrencia}/aprovar', [AnaliseController::class, 'aprovar'])->name('aprovar');
        Route::post('/{ocorrencia}/reprovar', [AnaliseController::class, 'reprovar'])->name('reprovar');
    });

    // ---------- Ordens de serviço (Gerente) ----------
    Route::middleware('role:gerente')->prefix('ordens-servico')->name('ordens-servico.')->group(function () {
        Route::get('/', [OrdemServicoController::class, 'index'])->name('index');
        Route::get('/{ordem_servico}', [OrdemServicoController::class, 'show'])->name('show');
        Route::post('/{ordem_servico}/transicao', [OrdemServicoController::class, 'transicionar'])->name('transicionar');
    });

    // ---------- Relatórios (Supervisor, Gerente e Master) ----------
    Route::middleware('role:supervisor,gerente')->prefix('relatorios')->name('relatorios.')->group(function () {
        Route::get('/', [RelatorioController::class, 'index'])->name('index');
        Route::get('/ficha-os/{ordem_servico}', [RelatorioController::class, 'fichaOs'])->name('ficha-os');
        Route::get('/{tipo}', [RelatorioController::class, 'show'])->name('show');
        Route::get('/{tipo}/pdf', [RelatorioController::class, 'pdf'])->name('pdf');
    });

    // ---------- Cadastros (Master) ----------
    Route::middleware('role:master')->prefix('cadastros')->group(function () {
        $cadastros = [
            'usuarios' => [UsuarioController::class, 'usuario'],
            'areas' => [AreaController::class, 'area'],
            'equipamentos' => [EquipamentoController::class, 'equipamento'],
            'categorias' => [CategoriaController::class, 'categoria'],
            'motivos' => [MotivoReprovacaoController::class, 'motivo'],
            'equipes' => [EquipeController::class, 'equipe'],
        ];

        foreach ($cadastros as $nome => [$controller, $param]) {
            Route::resource($nome, $controller)->except('show')->parameters([$nome => $param]);
            Route::patch("{$nome}/{{$param}}/alternar", [$controller, 'alternar'])->name("{$nome}.alternar");
        }

        Route::put('usuarios/{usuario}/senha', [UsuarioController::class, 'resetarSenha'])->name('usuarios.senha');

        Route::get('auditoria', [LogController::class, 'index'])->name('logs.index');

        Route::get('sla', [SlaPrazoController::class, 'index'])->name('sla.index');
        Route::put('sla', [SlaPrazoController::class, 'update'])->name('sla.update');
    });
});
