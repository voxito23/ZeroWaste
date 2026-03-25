<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    
    const UPDATED_AT = null;

    protected $fillable = [
        'nombre',
        'direccion',
        'latitud',
        'longitud',
        'tipo',
        'materiales',
    ];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
