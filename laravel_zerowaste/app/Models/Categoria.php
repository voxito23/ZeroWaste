<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nombre
 * @method static \Illuminate\Database\Eloquent\Builder|Categoria query()
 */
class Categoria extends Model
{
    protected $table = 'categorias';
    public $timestamps = false;

    protected $fillable = ['nombre'];

    public function posts()
    {
        return $this->hasMany(Post::class, 'categoria_id');
    }
}
