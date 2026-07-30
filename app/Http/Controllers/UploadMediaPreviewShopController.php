<?php

namespace App\Http\Controllers;

use App\Helper;
use FileUploader;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Typography\FontFactory;

class UploadMediaPreviewShopController extends Controller
{

	public function __construct(AdminSettings $settings, Request $request)
	{
		$this->settings = $settings::first();
		$this->request = $request;
		$this->middleware('auth');
	}

	/**
	 * submit the form
	 *
	 * @return void
	 */
	public function store()
	{
		$publicPath = public_path('temp/');
		$file = strtolower(auth()->id() . uniqid() . time() . str_random(20));

		// initialize FileUploader
		$FileUploader = new FileUploader('preview', array(
			'limit' => 5,
			'fileMaxSize' => floor($this->settings->file_size_allowed / 1024),
			'extensions' => [
				'png',
				'jpeg',
				'jpg',
				'heic',
				'heif',
				'ief',
			],
			'title' => $file,
			'uploadDir' => $publicPath
		));

		// upload
		$upload = $FileUploader->upload();

		if ($upload['isSuccess']) {

			foreach ($upload['files'] as $key => $item) {
				$upload['files'][$key] = [
					'extension' => $item['extension'],
					'format' => $item['format'],
					'name' => $item['name'],
					'size' => $item['size'],
					'size2' => $item['size2'],
					'type' => $item['type'],
					'uploaded' => true,
					'replaced' => false
				];

				$this->resizeImage($item['name']);
			} // foreach

		} // upload isSuccess

		return response()->json($upload);
	}

	/**
	 * Resize image and add watermark
	 *
	 * @return void
	 */
	protected function resizeImage($image)
	{
		$image = public_path('temp/') . $image;
		$img = Image::read($image);
		$url = ucfirst(Helper::urlToDomain(url('/')));
		$username = auth()->user()->username;

		$width = $img->width();

		// Image Large
		$scale = $width > 2000 ? 2000 : $width;

		$img = $img->scale(width: $scale);

		$fontSize = max(12, round($img->width() * 0.03));

		if (config('settings.watermark') == 'on') {
			$img->text($url . '/' . $username, $img->width() - 20, $img->height() - 10, function (FontFactory $font)
			use ($fontSize) {
				$font->filename(public_path('webfonts/arial.TTF'));
				$font->size($fontSize);
				$font->color('#eaeaea');
				$font->stroke('000000', 1);
				$font->align('right');
				$font->valign('bottom');
			});
		}

		$img->save();
	}

	/**
	 * delete a file
	 *
	 * @return void
	 */
	public function delete()
	{
		// PATH
		$local = 'temp/';

		// Delete local file
		Storage::disk('default')->delete($local . $this->request->file);

		return response()->json([
			'success' => true
		]);
	}

}
