<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conductor extends Model
{
    protected $table = 'conductores';
    protected $fillable = [
        'nombres',
        'apellidos',
        'documento',
        'telefono',
        'email',
        'licencia_numero',
        'licencia_categoria',
        'licencia_vencimiento',
        'estado'
    ];
    public $timestamps = true;
}