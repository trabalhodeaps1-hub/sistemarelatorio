<?php

namespace App\Services;

use App\Enums\StatusOS;
use App\Enums\TipoFoto;
use App\Models\Ocorrencia;
use App\Models\OrdemServico;
use App\Models\SlaPrazo;
use App\Models\User;
use App\Notifications\OSConcluida;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrdemServicoService
{
    public function __construct(
        private NumeracaoService $numeracao,
        private FotoService $fotos,
    ) {}

    /** Abre a OS de uma ocorrência aprovada (chamado dentro da transação da análise). */
    public function abrirParaOcorrencia(Ocorrencia $ocorrencia, User $user): OrdemServico
    {
        $os = OrdemServico::create([
            'ocorrencia_id' => $ocorrencia->id,
            'status' => StatusOS::Aberta,
            'criticidade' => $ocorrencia->criticidade,
            'prazo_previsto' => now()->addHours(SlaPrazo::horasPara($ocorrencia->criticidade)),
        ]);

        $this->numeracao->atribuir($os, 'OS');

        $os->historico()->create([
            'user_id' => $user->id,
            'status_anterior' => null,
            'status_novo' => StatusOS::Aberta,
            'comentario' => "OS gerada pela aprovação da ocorrência {$ocorrencia->numero}.",
        ]);

        return $os;
    }

    /**
     * Executa uma transição da máquina de estados (StatusOS::transicoesPermitidas),
     * aplicando as exigências de cada destino, preenchendo datas e gravando o histórico.
     *
     * @param  array{equipe_id?: int|null, prazo_previsto?: string|null, comentario?: string|null, observacao_conclusao?: string|null}  $dados
     * @param  array<int, UploadedFile>  $fotosDepois
     */
    public function transicionar(OrdemServico $os, StatusOS $novo, User $user, array $dados = [], array $fotosDepois = []): OrdemServico
    {
        $os = DB::transaction(function () use ($os, $novo, $user, $dados, $fotosDepois) {
            /** @var OrdemServico $os */
            $os = OrdemServico::lockForUpdate()->findOrFail($os->id);
            $anterior = $os->status;

            if (! $anterior->podeTransicionarPara($novo)) {
                throw ValidationException::withMessages([
                    'transicao' => "Transição inválida: {$anterior->label()} → {$novo->label()}.",
                ])->status(422);
            }

            $comentario = $dados['comentario'] ?? null;

            match ($novo) {
                StatusOS::Programada => $this->programar($os, $dados),
                StatusOS::EmExecucao => $os->iniciada_em ??= now(),
                StatusOS::Concluida => $this->concluir($os, $dados, $fotosDepois, $user),
                StatusOS::Cancelada => $this->cancelar($os, $comentario),
                default => null,
            };

            $os->status = $novo;
            $os->save();

            $os->historico()->create([
                'user_id' => $user->id,
                'status_anterior' => $anterior,
                'status_novo' => $novo,
                'comentario' => $novo === StatusOS::Concluida ? ($dados['observacao_conclusao'] ?? $comentario) : $comentario,
            ]);

            return $os;
        });

        if ($os->status === StatusOS::Concluida) {
            $os->load('ocorrencia.user');
            $tecnico = $os->ocorrencia->user;
            if ($tecnico && $tecnico->ativo) {
                $tecnico->notify(new OSConcluida($os));
            }
        }

        return $os;
    }

    private function programar(OrdemServico $os, array $dados): void
    {
        if (empty($dados['equipe_id'])) {
            throw ValidationException::withMessages(['equipe_id' => 'Informe a equipe para programar a OS.']);
        }

        $os->equipe_id = $dados['equipe_id'];
        $os->responsavel_id ??= auth()->id();

        if (! empty($dados['prazo_previsto'])) {
            $os->prazo_previsto = Carbon::parse($dados['prazo_previsto']);
            $os->atraso_notificado_em = null;
        }
    }

    private function concluir(OrdemServico $os, array $dados, array $fotosDepois, User $user): void
    {
        $observacao = trim((string) ($dados['observacao_conclusao'] ?? ''));
        $temFotos = count($fotosDepois) > 0
            || $os->ocorrencia->fotos()->where('tipo', TipoFoto::Depois->value)->exists();

        $erros = [];
        if ($observacao === '') {
            $erros['observacao_conclusao'] = 'Descreva o serviço executado para concluir a OS.';
        }
        if (! $temFotos) {
            $erros['fotos'] = 'Envie pelo menos 1 foto de "depois" para concluir a OS.';
        }
        if ($erros) {
            throw ValidationException::withMessages($erros);
        }

        $this->fotos->salvarVarias($fotosDepois, $os->ocorrencia, TipoFoto::Depois, $user);

        $os->observacao_conclusao = $observacao;
        $os->concluida_em = now();
        $os->iniciada_em ??= now();
    }

    private function cancelar(OrdemServico $os, ?string $justificativa): void
    {
        if (blank($justificativa)) {
            throw ValidationException::withMessages(['comentario' => 'Informe a justificativa do cancelamento.']);
        }

        $os->cancelada_em = now();
    }
}
