<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $email
 * @property string $token
 * @property string $estado
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|PasswordResetRequest query()
 * @method static int count()
 */
class PasswordResetRequest extends Model
{
    protected $table = 'password_reset_requests';
    public $timestamps = false;

    protected $fillable = [
        'email', 'estado', 'notas', 'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
