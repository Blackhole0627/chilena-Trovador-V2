<?php

namespace App\Models;

use App\Helper;
use Illuminate\Database\Eloquent\Model;

class Vault extends Model
{
  protected $fillable = [
    'user_id',
    'type',
    'image',
    'width',
    'height',
    'img_type',
    'video',
    'encoded',
    'video_poster',
    'duration_video',
    'quality_video',
    'video_embed',
    'music',
    'file',
    'file_name',
    'file_size',
    'bytes',
    'mime',
    'status',
    'job_id',
    'created_at'
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  protected static function boot()
  {
    parent::boot();

    static::deleting(function ($vault) {
      MediaMessages::where('vault_id', $vault->id)->delete();
    });
  }

  public function getPreviewAttribute()
  {
    $previewDefault = url('public/img/placeholder.png');
    $preview = $this->type == 'image'
      ? $this->file
      : ($this->video_poster ?? null);

    $previewFile = $preview ? Helper::getFile(config('path.vault') . $preview) : $previewDefault;

    return $previewFile;
  }
}
