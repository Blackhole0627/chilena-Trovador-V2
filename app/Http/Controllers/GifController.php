<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GiphyService;

class GifController extends Controller
{
    public function __construct(protected GiphyService $giphy) {}

    public function getGifs()
    {
        return view('includes.gifs-items', ['gifs' => $this->giphy->getTrending()])->render();
    }

    public function searchGifs(Request $request)
    {
        $searchGifs = $this->giphy->search($request->q);
        $gifs = $gifs = $searchGifs->filter()->isEmpty() ? $this->giphy->getTrending() : $searchGifs;

        return view('includes.item-gif', ['gifs' => $gifs])->render();
    }
}
