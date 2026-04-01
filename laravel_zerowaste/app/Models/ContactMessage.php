<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @property string $email
 * @property string|null $ubicacion
 * @property string $mensaje
 * @property string $estado
 * @property string|null $respuesta_admin
 * @property int|null $usuario_id
 * @property string $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|ContactMessage query()
 * @method static int count()
 * @method static ContactMessage findOrFail(mixed $id)
 * @method static \Illuminate\Database\Eloquent\Builder|ContactMessage orderByDesc(string $column)
 * @method \Illuminate\Database\Eloquent\Relations\BelongsTo belongsTo(string $related, string|null $foreignKey = null)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ContactMessage extends Model
{
    protected $table = 'contact_messages';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'nombre', 'email', 'ubicacion', 'mensaje', 'estado', 'respuesta_admin', 'usuario_id', 'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
