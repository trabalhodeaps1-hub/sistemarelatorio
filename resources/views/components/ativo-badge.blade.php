@props(['ativo'])
<span class="badge {{ $ativo ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $ativo ? 'Ativo' : 'Inativo' }}</span>
