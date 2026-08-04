<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de solicitudes ARCO+ (Ley 21.719).
 * Acceso, Rectificacion, Cancelacion/Supresion, Oposicion y Portabilidad.
 */
class ArcoRequest extends Model
{
    protected $fillable = ['user_id', 'type', 'details', 'status'];

    // Convencion Sponzy: la relacion devuelve el modelo (se llama como metodo)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->first();
    }
}
