<?php

namespace App\Http\Controllers;

use App\Helper;
use FileUploader;
use App\Models\Media;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use App\Jobs\MediaModeration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Typography\FontFactory;

class UploadMediaController extends Controller
{
	public function __construct(Request $request)
	{
		$this->request = $request;
		$this->status = $this->request->postId ? 'active' : 'pending';
		$this->postId = $this->request->postId ?: 0;
	}

	/**
	 * submit the form
	 */
	public function store(): JsonResponse
	{
		$publicPath = public_path('temp/');
		$file = strtolower(auth()->id() . uniqid() . time() . str_random(20));

		if (config('settings.video_encoding') == 'off') {
			$extensions = ['png', 'jpeg', 'jpg', 'gif', 'ief', 'video/mp4', 'audio/x-matroska', 'audio/mpeg'];
		} else {
			$extensions = [
				'png',
				'jpeg',
				'jpg',
				'gif',
				'ief',
				'heic',
				'heif',
				'video/mp4',
				'video/quicktime',
				'video/3gpp',
				'video/mpeg',
				'video/x-matroska',
				'video/x-ms-wmv',
				'video/vnd.avi',
				'video/avi',
				'video/x-flv',
				'audio/x-matroska',
				'audio/mpeg'
			];
		}

		// initialize FileUploader
		$FileUploader = new FileUploader('photo', array(
			'limit' => config('settings.maximum_files_post'),
			'fileMaxSize' => floor(config('settings.file_size_allowed') / 1024),
			'extensions' => $extensions,
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

				switch ($item['format']) {
					case 'image':
						$this->resizeImage($item);
						break;

					case 'video':
						$this->uploadVideo($item);
						break;

					case 'audio':
						$this->uploadMusic($item);
						break;
				}
			}
		}

		return response()->json($upload);
	}

	/**
	 * Resize image and add watermark
	 */
	protected function resizeImage($image): void
	{
		$fileName = $image['name'];
		$pathImage = public_path('temp/') . $image['name'];
		$img   = Image::read($pathImage);
		$token = str_random(150) . uniqid() . now()->timestamp;
		$url   = ucfirst(Helper::urlToDomain(url('/')));
		$username = auth()->user()->username;
		$path  = config('path.images');

		$width = $img->width();
		$height = $img->height();

		if ($image['extension'] == 'gif') {
			$this->insertImage($fileName, $width, $height, 'gif', $token, $image);

			// Move file to Storage
			$this->moveFileStorage($fileName, $path);
		} else {
			// Image Large
			$scale = $width > 2000 ? 2000 : $width;

			$img = $img->scale(width: $scale);

			$fontSize = max(12, round($img->width() * 0.03));

			if (config('settings.watermark') == 'on') {
				$img->text($url . '/' . $username, $img->width() - 30, $img->height() - 30, function (FontFactory $font)
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

			// Insert in Database
			$this->insertImage($fileName, $width, $height, null, $token, $image);

			// Move file to Storage
			$this->moveFileStorage($fileName, $path);
		}
	}


	/**
	 * Insert Image to Database
	 */
	protected function insertImage($fileName, $width, $height, $imgType, $token, $image): void
	{
		$media = Media::create([
			'updates_id' => $this->postId,
			'user_id' => auth()->id(),
			'type' => 'image',
			'image' => $fileName,
			'width' => $width,
			'height' => $height,
			'video' => '',
			'video_embed' => '',
			'music' => '',
			'file' => '',
			'file_name' => $image['old_name'],
			'file_size' => '',
			'bytes' => $image['size'],
			'mime' => $image['type'],
			'img_type' => $imgType ?? '',
			'token' => $token,
			'status' => $this->status,
			'created_at' => now()
		]);

		// Dispatch Media Moderation Videos
		if ($this->postId && config('settings.moderation_status')) {
			$media->update([
				'status' => 'pending'
			]);

			dispatch(new MediaModeration($media));
		}

	}

	/**
	 * Upload Video
	 */
	protected function uploadVideo($video): void
	{
		$status = config('settings.video_encoding') == 'off' ? $this->status : 'pending';

		Media::create([
			'updates_id' => $this->postId,
			'user_id' => auth()->id(),
			'type' => 'video',
			'image' => '',
			'video' => $video['name'],
			'video_poster' => '',
			'video_embed' => '',
			'music' => '',
			'file' => '',
			'file_name' => $video['old_name'],
			'file_size' => '',
			'bytes' => $video['size'],
			'mime' => $video['type'],
			'img_type' => '',
			'token' => $this->getToken(),
			'status' => $status,
			'created_at' => now()
		]);

		// Move file to Storage
		if (config('settings.video_encoding') == 'off') {
			$this->moveFileStorage($video['name'], config('path.videos'));
		}
	}

	/**
	 * Upload Music
	 */
	protected function uploadMusic($music): void
	{
		Media::create([
			'updates_id' => $this->postId,
			'user_id' => auth()->id(),
			'type' => 'music',
			'image' => '',
			'video' => '',
			'video_embed' => '',
			'music' => $music['name'],
			'file' => '',
			'file_name' => '',
			'file_size' => '',
			'bytes' => $music['size'],
			'mime' => $music['type'],
			'img_type' => '',
			'token' => $this->getToken(),
			'status' => $this->status,
			'created_at' => now()
		]);

		// Move file to Storage
		$this->moveFileStorage($music['name'], config('path.music'));
	}

	/**
	 * Move file to Storage
	 */
	protected function moveFileStorage($file, $path): void
	{
		$localFile = public_path('temp/' . $file);

		// Move the file...
		Storage::putFileAs($path, new File($localFile), $file);

		// Delete temp file
		unlink($localFile);
	}

	protected function getToken(): mixed
	{
		return str_random(150) . uniqid() . now()->timestamp;
	}

	/**
	 * delete a file
	 */
	public function delete()
	{
		$path = config('path.images');
		$pathVideo = config('path.videos');
		$pathMusic = config('path.music');
		$pathFile = config('path.files');
		$local = 'temp/';

		$media = Media::whereUserId(auth()->id())
			->whereImage($this->request->file)
			->orWhere('video', $this->request->file)
			->whereUserId(auth()->id())
			->orWhere('music', $this->request->file)
			->whereUserId(auth()->id())
			->orWhere('file', $this->request->file)
			->whereUserId(auth()->id())
			->first();

		if (!$media) {
			return false;
		}

		if ($media->image) {
			Storage::delete($path . $media->image);
			// Delete local file (if exist)
			Storage::disk('default')->delete($local . $media->image);

			$media->delete();
		}

		if ($media->video) {
			Storage::delete($pathVideo . $media->video);
			Storage::delete($pathVideo . $media->video_poster);
			// Delete local file (if exist)
			Storage::disk('default')->delete($local . $media->video);

			$media->delete();
		}

		if ($media->music) {
			Storage::delete($pathMusic . $media->music);
			// Delete local file (if exist)
			Storage::disk('default')->delete($local . $media->music);

			$media->delete();
		}

		if ($media->file) {
			Storage::delete($pathFile . $media->file);

			$media->delete();
		}

		return response()->json([
			'success' => true
		]);
	}
}
