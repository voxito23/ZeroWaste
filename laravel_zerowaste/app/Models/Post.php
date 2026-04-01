<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $titulo
 * @property string $contenido
 * @property int $categoria_id
 * @property int $autor_id
 * @property string $imagen
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|Post query()
 * @method static int count()
 */
class Post extends Model
{
    protected $table = 'posts';

    const UPDATED_AT = null;

    protected $fillable = [
        'titulo',
        'contenido',
        'categoria_id',
        'autor_id',
        'imagen',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    public function respuestas()
    {
        return $this->hasMany(Respuesta::class, 'post_id');
    }
}
