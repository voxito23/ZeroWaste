<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $post_id
 * @property int $autor_id
 * @property string $contenido
 * @property \Illuminate\Support\Carbon $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|Respuesta query()
 */
class Respuesta extends Model
{
    protected $table = 'respuestas';

    const UPDATED_AT = null;

    protected $fillable = [
        'post_id',
        'autor_id',
        'contenido',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
