<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\Advertising;
use Illuminate\Http\Request;
use App\Models\AdClickImpression;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

final class AdvertisingController extends Controller
{
    public $pathFolder = 'uploads/ads/';

    public function show(): View
    {
        return view('admin.advertising', [
            'ads' => Advertising::query()->latest()->get()
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'title' => 'required|max:50',
            'description' => 'required|max:150',
            'url' => 'required|url',
            'image' => 'required|mimes:jpg,png,jpe,jpeg|dimensions:min_width=400',
        ];

        $this->validate($request, $rules);

        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            $extension  = $request->file('image')->getClientOriginalExtension();
            $image = time() . '-' . str_random(32) . '.' . $extension;
            
            $imageResize = Image::read($imageFile)->scale(width: 400)->encodeByExtension($extension);

            Storage::put($this->pathFolder . $image, $imageResize);
        }

        $sql = new Advertising();
        $sql->title = $request->title;
        $sql->description = $request->description;
        $sql->url = $request->url;
        $sql->status = $request->get('status', false);
        $sql->expired_at = now()->addMonths(intval($request->expired_at));
        $sql->image = $image;
        $sql->save();

        return redirect('panel/admin/advertising')->withSuccessMessage(__('general.success_add_ad'));
    }

    public function edit(Advertising $ad): View
	{
		return view('admin.edit-advertising', ['ad' => $ad]);
	}

    public function update(Request $request): RedirectResponse
    {
        $ad = Advertising::findOrFail($request->id);

        $rules = [
            'title' => 'required|max:50',
            'description' => 'required|max:150',
            'url' => 'required|url',
            'image' => 'mimes:jpg,png,jpe,jpeg|dimensions:min_width=400',
        ];

        $this->validate($request, $rules);

        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');
            $extension  = $request->file('image')->getClientOriginalExtension();
            $image = time() . '-' . str_random(32) . '.' . $extension;

            $imageResize = Image::read($imageFile)->scale(width: 400)->encodeByExtension($extension);

            Storage::put($this->pathFolder . $image, $imageResize);

            Storage::delete($this->pathFolder . $ad->image);
        }

        $ad->title = $request->title;
        $ad->description = $request->description;
        $ad->url = $request->url;
        $ad->status = $request->updateExpirationDate ? true : $request->get('status', false);
        $ad->expired_at = $request->updateExpirationDate ? now()->addMonths(intval($request->expired_at)) : $ad->expired_at;
        $ad->image = $image ?? $ad->image;
        $ad->save();

        return redirect('panel/admin/advertising')->withSuccessMessage(__('general.success_update'));
    }

    public function destroy(Advertising $ad): RedirectResponse
	{
		// Delete image
		Storage::delete($this->pathFolder . $ad->image);

        $ad->delete();

		return back();
	}

    public static function clicksAds(Advertising $ad): RedirectResponse
    {
        $click = AdClickImpression::firstOrNew(
            [
              'advertisings_id' => $ad->id,
              'type' => 'click',
              'ip' => request()->ip()
            ]
          );

          if (!$click->exists) {
            $ad->increment('clicks');

            $click->save();
          }

        return redirect($ad->url);
    }
}
