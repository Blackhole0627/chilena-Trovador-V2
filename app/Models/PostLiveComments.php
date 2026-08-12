<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Comentarios en vivo (hilo plano) sobre publicaciones.
 * El creador puede responder con texto o una nota de voz (media).
 */
class PostLiveComments extends Model
{
    protected $fillable = [
        'user_id',
        'updates_id',
        'comment',
        'media',
        'sticker',
        'gif_image',
    ];

    // Convencion Sponzy: la relacion devuelve el modelo.
    public function user()
    {
        return $this->belongsTo(User::class)->first();
    }
}
