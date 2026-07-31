<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $nombre
 * @property string $lugar
 * @property string $fecha_inicio
 * @property string $fecha_fin
 * @property string $descripcion
 * @property string $tipo_etiqueta
 * @property string $imagen_url
 * @property string $link_evento
 * @property int $recompensa_puntos
 * @property bool $activa
 * @property string $created_at
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign query()
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign orderByDesc(string $column)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign whereBetween(string $column, array $values)
 * @method static \Illuminate\Database\Eloquent\Builder|Campaign limit(int $value)
 * @method bool|null delete()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany hasMany($related, $foreignKey = null, $localKey = null)
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin \Illuminate\Database\Query\Builder
 */
class Campaign extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    protected $table = 'campaigns';

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

    protected $appends = ['cover_url'];

    public function getCoverUrlAttribute(): ?string
    {
        return Media::url($this->imagen_url, 'campanas');
    }
}
