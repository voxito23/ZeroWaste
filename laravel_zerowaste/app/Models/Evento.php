<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use HasFactory;

    protected $table = 'eventos';
    public $timestamps = false;

    protected $fillable = [
        'titulo', 'lugar', 'fecha_inicio', 'fecha_fin', 
        'descripcion', 'tipo_etiqueta', 'imagen_url'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];
}
