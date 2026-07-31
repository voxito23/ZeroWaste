<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @property string $direccion
 * @property float $latitud
 * @property float $longitud
 * @property string $tipo
 * @property string|null $imagen
 * @property string|null $materiales
 * @method static \Illuminate\Database\Eloquent\Builder|Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Location query()
 * @method static \Illuminate\Database\Eloquent\Builder|Location create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Location find($id, $columns = ['*'])
 * @method bool update(array $attributes = [], array $options = [])
 * @method bool|null delete()
 * @method \Illuminate\Database\Eloquent\Relations\HasMany hasMany($related, $foreignKey = null, $localKey = null)
 */
class Location extends Model
{
    use HasFactory;
    
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;
    protected $table = 'locations';

    protected $fillable = [
        'nombre',
        'direccion',
        'latitud',
        'longitud',
        'tipo',
        'imagen',
        'materiales',
    ];

    protected $appends = ['image_url'];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return Media::url($this->imagen, 'puntos');
    }
}
