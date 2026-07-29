<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $nombre
 * @property string $apellidos
 * @property string $email
 * @property string $password
 * @property bool $is_admin
 * @property string|null $foto_perfil
 * @property string|null $titulo_perfil
 * @property string|null $biografia
 * @property string|null $intereses
 * @property string|null $firebase_uid
 * @property string|null $auth_provider
 * @property bool $profile_completed
 * @method static \Illuminate\Database\Eloquent\Collection|static[] all($columns = ['*'])
 * @method static static create(array $attributes = [])
 * @method static static find(int $id)
 * @method static static updateOrCreate(array $attributes, array $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method bool update(array $attributes = [], array $options = [])
 * @method bool delete()
 * @method bool save()
 * @method static \App\Models\User|null firstWhere(string|array $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'nombre', 'email', 'password', 'is_admin', 'foto_perfil',
        'ubicacion', 'titulo_perfil', 'biografia', 'intereses',
        'firebase_uid', 'auth_provider', 'profile_completed', 'bloqueado',
        'rol', 'edad', 'licencia_conducir'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'profile_completed' => 'boolean',
            'bloqueado' => 'boolean',
        ];
    }
}
