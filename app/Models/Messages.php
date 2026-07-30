<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{
    protected $guarded = [];

    public static function conversations()
    {
        $fields = 'id,avatar,name,username,hide_name,status,verified_id,active_status_online';
        $filter = request('filter');
        $search = trim(request('q', ''));
        $authId = (int) auth()->id();

        if (! in_array($filter, ['newest', 'unread', 'oldest'])) {
            $filter = 'newest';
        }

        $query = self::from('messages as m1')
            ->select('m1.*')
            ->join(DB::raw(
                '(
          SELECT
              LEAST(from_user_id, to_user_id) AS from_user_id,
              GREATEST(from_user_id, to_user_id) AS to_user_id,
              MAX(id) AS max_id
          FROM messages
          GROUP BY
              LEAST(from_user_id, to_user_id),
              GREATEST(from_user_id, to_user_id)
      ) AS m2'
            ), fn ($join) => $join
                ->on(DB::raw('LEAST(m1.from_user_id, m1.to_user_id)'), '=', 'm2.from_user_id')
                ->on(DB::raw('GREATEST(m1.from_user_id, m1.to_user_id)'), '=', 'm2.to_user_id')
                ->on('m1.id', '=', 'm2.max_id'))
            ->where(fn ($query) => $query
                ->where('m1.from_user_id', auth()->id())
                ->orWhere('m1.to_user_id', auth()->id()));

        $query->when(
            $filter == 'unread',
            fn ($q) => $q->where('m1.to_user_id', auth()->id())
                ->where('m1.status', 'new')
        );

        $query->when(
            mb_strlen($search) >= 3,
            fn ($q) => $q->join('users as u', fn ($join) => $join
                ->on(DB::raw("CASE WHEN m1.from_user_id = {$authId} THEN m1.to_user_id ELSE m1.from_user_id END"), '=', 'u.id'))
                ->where(fn ($builder) => $builder
                    ->where('u.username', 'like', "%{$search}%")
                    ->orWhere('u.name', 'like', "%{$search}%"))
        );

        if ($filter == 'oldest') {
            $query->orderBy('m1.created_at', 'asc')
                ->orderBy('m1.id', 'asc');
        } else {
            $query->orderByDesc('m1.created_at')
                ->orderByDesc('m1.id');
        }

        return $query->with(['sender:'.$fields, 'receiver:'.$fields, 'media'])
            ->simplePaginate(15);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function remitter()
    {
        if ($this->from_user_id == auth()->id()) {
            return $this->receiver;
        }

        return $this->sender;
    }

    public function remitterName()
    {
        return $this->remitter()->hide_name == 'yes'
          ? $this->remitter()->username
          : $this->remitter()->name;
    }

    public function totalMsg()
    {
        return $this->where('from_user_id', $this->remitter()->id)
            ->where('to_user_id', auth()->id())
            ->where('status', 'new')
            ->count();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'from_user_id')->first();
    }

    public static function markSeen()
    {
        $this->timestamps = false;
        $this->status = 'readed';
        $this->save();
    }

    public function media()
    {
        return $this->hasMany(MediaMessages::class)->where('status', 'active')->orderBy('id', 'asc');
    }

    public function scopeGetMessageChat($query, $id, $skip = null)
    {
        $fields = 'id,avatar,name,username';

        $query->where('to_user_id', auth()->id())
            ->where('from_user_id', $id)
            ->whereMode('active')
            ->orWhere('from_user_id', auth()->id())
            ->where('to_user_id', $id)
            ->whereMode('active');

        $query->when(
            $skip,
            fn ($q) => $q->skip($skip)
        );

        $query = $query->take(10)
            ->orderBy('messages.id', 'DESC')
            ->with(['sender:'.$fields, 'receiver:'.$fields, 'media'])
            ->get();

        return $query;
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class)->where('status', true);
    }

    public function vault()
    {
        return $this->hasMany(Vault::class);
    }
}
