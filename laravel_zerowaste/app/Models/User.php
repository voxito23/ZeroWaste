<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** Habilita la creación de registros mediante factories. */
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    /**
     * Atributos asignables de forma masiva.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'email',
        'password',
        'is_admin',
        'foto_perfil',
        'ubicacion',
        'titulo_perfil',
        'biografia',
        'intereses'
    ];

    /**
     * Atributos ocultos en la serialización.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Definición de casteos de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
}
