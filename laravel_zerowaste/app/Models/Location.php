<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'direccion',
        'latitud',
        'longitud',
        'tipo',
        'materiales',
        'imagen',
    ];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
