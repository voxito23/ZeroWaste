<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @property string $tipo
 * @property string $unidades_medida
 * @property int $valor_puntos
 * @method static \Illuminate\Database\Eloquent\Builder|Material query()
 */
class Material extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'tipo',
        'unidades_medida',
        'valor_puntos',
    ];
}
