@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => 'Selecione...', 'help' => null, 'required' => false])
@php
    $id = $attributes->get('id', $name);
    $selecionado = old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}@if ($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <select name="{{ $name }}" id="{{ $id }}" @if ($required) required @endif
        {{ $attributes->except('id')->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
        @if ($placeholder !== false)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $valor => $rotulo)
            <option value="{{ $valor }}" @selected((string) $selecionado === (string) $valor)>{{ $rotulo }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
