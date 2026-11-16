<?php

namespace App\Http\Controllers\Cadastros;

use App\Enums\Criticidade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cadastros\SlaPrazoRequest;
use App\Models\SlaPrazo;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SlaPrazoController extends Controller
{
    public function index(): View
    {
        $horas = collect(Criticidade::cases())
            ->mapWithKeys(fn (Criticidade $c) => [$c->value => SlaPrazo::horasPara($c)])
            ->all();

        return view('cadastros.sla.index', ['horas' => $horas, 'criticidades' => Criticidade::cases()]);
    }

    public function update(SlaPrazoRequest $request): RedirectResponse
    {
        foreach ($request->validated('horas') as $criticidade => $horas) {
            SlaPrazo::updateOrCreate(['criticidade' => $criticidade], ['horas' => $horas]);
        }

        return back()->with('success', 'Prazos de SLA atualizados. Valem para as próximas OS geradas.');
    }
}
