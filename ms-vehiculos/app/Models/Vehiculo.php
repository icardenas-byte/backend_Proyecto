<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    public $timestamps = false;

    protected $fillable = [
        'placa',
        'tipo',
        'capacidad_carga',
        'modelo',
        'marca',
        'estado',
    ];
}
