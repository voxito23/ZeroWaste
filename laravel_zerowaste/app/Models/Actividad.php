<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $usuario_id
 * @property string $tipo
 * @property string $descripcion
 * @property \Illuminate\Support\Carbon $fecha_creacion
 * @method static \Illuminate\Database\Eloquent\Builder|Actividad query()
 * @method static int count()
 */
class Actividad extends Model
{
    protected $table = 'actividades';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'tipo',
        'descripcion',
        'fecha_creacion',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
