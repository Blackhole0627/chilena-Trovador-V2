<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use App\Models\Sticker;
use Illuminate\Http\Request;

class StickerController extends Controller
{
    public function show()
    {
        return view(
            'admin.stickers',
            [
                'data' => Sticker::latest()->paginate(20)
            ]
        );
    }

    public function store(Request $request)
    {
        Sticker::create([
            'url' => $request->url
        ]);

        return redirect()->route('stickers')->withSuccess(__('general.successfully_added'));
    }

    public function destroy($id)
    {
        $sticker = Sticker::findOrFail($id);
        $sticker->delete();

        return redirect()->route('stickers')->withSuccess(__('general.successfully_removed'));
    }

    public function getStickers()
    {
        $stickers = Sticker::all();

        return view('includes.sticker-items', ['stickers' => $stickers])->render();
    }
}
