<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

class Viaje extends Model
{
    public $timestamps = false;

    protected $table = 'seguimientos_viajes';

    protected $fillable = [
        'programacion_viaje_id',
        'fecha',
        'hora',
        'estado',
        'novedad'
    ];

}

