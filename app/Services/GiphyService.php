<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GiphyService
{
    protected string $baseUrl = 'https://api.giphy.com/v1/gifs';
    protected int $limit = 25;

    public function getTrending(): mixed
    {
        $response = Http::get("{$this->baseUrl}/trending", [
            'api_key' => config('settings.giphy_api_key'),
            'limit' => $this->limit
        ]);

        return collect($response->json('data')) ?? [];
    }

    public function search(string $query): mixed
    {
        $response = Http::get("{$this->baseUrl}/search", [
            'api_key' => config('settings.giphy_api_key'),
            'q'       => $query,
            'limit'   => $this->limit,
            'lang' => app()->getLocale(),
        ]);

        return collect($response->json('data')) ?? [];
    }
}
