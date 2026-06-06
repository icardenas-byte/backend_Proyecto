<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

class ProgramacionViaje extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conductor_id',
        'vehiculo_id',
        'ruta_id',
        'fecha_salida',
        'hora_salida',
        'fecha_estimada_llegada',
        'observaciones',
        'estado',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = self::tablaDisponible();
    }

    public static function tablaDisponible(): string
    {
        foreach (['programaciones_viajes', 'programacion_viajes', 'programaciones', 'viajes_programados', 'viajes'] as $tabla) {
            if (DB::schema()->hasTable($tabla)) {
                return $tabla;
            }
        }

        return 'programaciones_viajes';
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }
}
