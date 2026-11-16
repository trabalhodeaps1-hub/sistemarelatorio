@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])
@php
    $id = $attributes->get('id', str_replace(['[', ']'], ['_', ''], $name));
    $chave = str_replace(['[', ']'], ['.', ''], $name);
    $valor = $type === 'password' ? null : old($chave, $value);
@endphp
<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }}@if ($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" value="{{ $valor }}"
        @if ($required) required @endif
        {{ $attributes->except('id')->class(['form-control', 'is-invalid' => $errors->has($chave)]) }}>
    @error($chave)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
