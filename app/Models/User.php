<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\RegistraAtividade;
use App\Models\Concerns\TemAtivo;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, RegistraAtividade, SoftDeletes, TemAtivo;

    protected $fillable = [
        'name',
        'matricula',
        'email',
        'password',
        'role',
        'ativo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'ativo' => 'boolean',
        ];
    }

    public function hasRole(Role|string ...$roles): bool
    {
        foreach ($roles as $role) {
            $role = $role instanceof Role ? $role : Role::from($role);
            if ($this->role === $role) {
                return true;
            }
        }

        return false;
    }

    public function isMaster(): bool
    {
        return $this->role === Role::Master;
    }

    public function iniciais(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');
    }

    public function scopeComRole(Builder $query, Role ...$roles): Builder
    {
        return $query->whereIn('role', array_map(fn (Role $r) => $r->value, $roles));
    }

    public function ocorrencias(): HasMany
    {
        return $this->hasMany(Ocorrencia::class);
    }

    public function analises(): HasMany
    {
        return $this->hasMany(OcorrenciaAnalise::class);
    }

    public function possuiVinculos(): bool
    {
        return $this->ocorrencias()->withTrashed()->exists()
            || $this->analises()->exists()
            || OrdemServico::where('responsavel_id', $this->id)->exists();
    }
}
