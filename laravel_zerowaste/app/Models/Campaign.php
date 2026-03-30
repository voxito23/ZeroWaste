<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    public $timestamps = false; // Solo se utiliza created_at, sin updated_at

    protected $fillable = [
        'nombre',
        'lugar',
        'fecha_inicio',
        'fecha_fin',
        'descripcion',
        'tipo_etiqueta',
        'imagen_url',
        'link_evento',
        'recompensa_puntos',
        'activa',
        'created_at'
    ];
}
