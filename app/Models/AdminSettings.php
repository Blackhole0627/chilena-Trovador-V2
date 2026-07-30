<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSettings extends Model
{
	protected $guarded = [];
	public $timestamps = false;

	protected static function getValueOf($field)
	{
		return static::select($field)->value($field);
	}
}
