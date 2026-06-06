<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

class Viaje extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'programacion_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = self::tablaDisponible();
    }

    public static function tablaDisponible(): string
    {
        foreach (['viajes', 'seguimiento_viajes'] as $tabla) {
            if (DB::schema()->hasTable($tabla)) {
                return $tabla;
            }
        }

        return 'viajes';
    }

    public function novedades()
    {
        return $this->hasMany(Novedad::class, 'viaje_id');
    }
}
