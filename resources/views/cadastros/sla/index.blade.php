@extends('layouts.app')
@section('title', 'SLA')

@section('content')
    <x-page-header titulo="Prazos de SLA" subtitulo="Prazo de atendimento da OS por criticidade, contado a partir da aprovação" />

    <form method="POST" action="{{ route('sla.update') }}" class="card" style="max-width: 640px">
        @csrf @method('PUT')
        <div class="card-body">
            @foreach ($criticidades as $c)
                @php $campo = "horas.{$c->value}"; @endphp
                <div class="row align-items-center mb-3">
                    <label for="horas_{{ $c->value }}" class="col-5 col-form-label"><x-status-badge :status="$c" /></label>
                    <div class="col-7">
                        <div class="input-group has-validation">
                            <input type="number" min="1" max="8760" name="horas[{{ $c->value }}]" id="horas_{{ $c->value }}"
                                   value="{{ old($campo, $horas[$c->value]) }}" class="form-control @error($campo) is-invalid @enderror" required>
                            <span class="input-group-text">horas</span>
                            @error($campo)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text">≈ {{ round($horas[$c->value] / 24, 1) }} dia(s)</div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="card-footer bg-white text-end">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar prazos</button>
        </div>
    </form>
@endsection
