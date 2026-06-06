<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Capsule\Manager as DB;

class Novedad extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'viaje_id',
        'tipo',
        'descripcion',
        'observaciones',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = self::tablaDisponible();
    }

    public static function tablaDisponible(): string
    {
        foreach (['novedades', 'novedades_viajes', 'historial_viajes'] as $tabla) {
            if (DB::schema()->hasTable($tabla)) {
                return $tabla;
            }
        }

        return 'novedades';
    }
}
