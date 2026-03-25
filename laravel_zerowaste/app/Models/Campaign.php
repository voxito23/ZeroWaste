<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    public $timestamps = false; // We use created_at but not updated_at by default in SQLAlchemy

    protected $fillable = [
        'nombre',
        'descripcion',
        'recompensa_puntos',
        'activa',
        'created_at'
    ];
}
