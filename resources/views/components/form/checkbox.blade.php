@props(['name', 'label', 'checked' => false, 'help' => null])
@php $id = $attributes->get('id', $name); @endphp
<div class="form-check form-switch mb-3">
    <input type="hidden" name="{{ $name }}" value="0">
    <input class="form-check-input @error($name) is-invalid @enderror" type="checkbox" role="switch"
           name="{{ $name }}" id="{{ $id }}" value="1" @checked(old($name, $checked)) {{ $attributes->except('id') }}>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
