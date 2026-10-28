<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Rol;
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
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'email',
        'password',
        'rol',
        'activo',
        'telefono',
        'cargo',
        'avatar',
        'bio',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
            'activo' => 'boolean',
        ];
    }

    // ---------------------------------------------------------------- Relaciones

    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class);
    }

    public function perfiles(): HasMany
    {
        return $this->hasMany(Perfil::class);
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function logsAcciones(): HasMany
    {
        return $this->hasMany(LogAccion::class);
    }

    public function reportesGenerados(): HasMany
    {
        return $this->hasMany(ReporteGenerado::class);
    }

    public function conversaciones(): HasMany
    {
        return $this->hasMany(ChatbotHistorial::class);
    }

    // ------------------------------------------------------------------- Scopes

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeConRol(Builder $query, Rol $rol): Builder
    {
        return $query->where('rol', $rol);
    }

    // ----------------------------------------------------------------- Permisos

    public function esAdministrador(): bool
    {
        return $this->rol === Rol::Administrador;
    }

    public function estaActivo(): bool
    {
        return $this->activo === true;
    }

    public function puedeControlarMaquina(): bool
    {
        return $this->rol->puedeControlarMaquina();
    }

    public function puedeVerReportes(): bool
    {
        return $this->rol->puedeVerReportes();
    }

    public function puedeGestionarUsuarios(): bool
    {
        return $this->rol->puedeGestionarUsuarios();
    }

    public function avatarEmoji(): ?string
    {
        $avatares = config('panel.avatares', []);

        return is_string($this->avatar) ? ($avatares[$this->avatar] ?? null) : null;
    }

    public function inicial(): string
    {
        return mb_strtoupper(mb_substr($this->nombre, 0, 1));
    }
}
