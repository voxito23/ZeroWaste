<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $titulo
 * @property string|null $lugar
 * @property \Illuminate\Support\Carbon|null $fecha_inicio
 * @property \Illuminate\Support\Carbon|null $fecha_fin
 * @property string $descripcion
 * @property string|null $tipo_etiqueta
 * @property string|null $imagen_url
 * @property string|null $link_evento
 * @method static \Illuminate\Database\Eloquent\Builder|Evento query()
 */
class Evento extends Model
{
    use HasFactory;

    protected $table = 'eventos';
    public $timestamps = false;

    protected $fillable = [
        'titulo', 'lugar', 'fecha_inicio', 'fecha_fin', 
        'descripcion', 'tipo_etiqueta', 'imagen_url', 'link_evento', 'activa'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    protected $appends = ['cover_url'];

    public function getCoverUrlAttribute(): ?string
    {
        return Media::url($this->imagen_url, 'eventos');
    }
}
