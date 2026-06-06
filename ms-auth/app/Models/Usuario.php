<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';

    protected $fillable = [
        'usuario',
        'email',
        'password',
        'token',
        'session_active',
    ];

    public $timestamps = false;

    protected $hidden = ['password'];
}
