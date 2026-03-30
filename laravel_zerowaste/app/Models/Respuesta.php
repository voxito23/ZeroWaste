<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
