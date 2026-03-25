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
        'titulo',
        'fecha_inicio',
        'ubicacion',
        'descripcion',
        'categoria',
        'imagen',
        'link_unirse',
    ];
}
