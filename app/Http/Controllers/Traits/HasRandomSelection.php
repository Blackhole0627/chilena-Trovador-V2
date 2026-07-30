<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Collection;

trait HasRandomSelection
{
    public static function getRandomRecords(int $limit = 25, int $poolSize = 100, \Closure $baseQuery = null, array $with = []): Collection
    {
        $query = static::query();

        if ($baseQuery) {
            $baseQuery($query);
        }

        $count = (clone $query)->count();

        if ($count <= $limit * 2) {
            return $query->with($with)->get()->shuffle()->take($limit);
        }

        $ids = (clone $query)
            ->inRandomOrder()
            ->limit($poolSize)
            ->pluck('id')
            ->shuffle()
            ->take($limit);

        return static::with($with)
            ->whereIn('id', $ids)
            ->get()
            ->shuffle();
    }
}
