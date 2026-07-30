<?php

namespace App\Http\Controllers;

use App\Helper;
use FileUploader;
use Illuminate\Http\File;
use App\Models\MediaStories;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Typography\FontFactory;

class UploadMediaStoryController extends Controller
{

	public function __construct(AdminSettings $settings, Request $request)
	{
		$this->settings = $settings::select(
			'maximum_files_post',
			'file_size_allowed',
			'watermark',
			'video_encoding'
			)->first();
		$this->request = $request;
	}

	/**
     * Submit the form
     *
     * @return void
     */
	public function store() 
	{
		$publicPath = public_path('temp/');
		$file = strtolower(auth()->id().uniqid().time().str_random(20));

		if ($this->settings->video_encoding == 'off') {
			$extensions = ['png','jpeg','jpg','gif','ief','video/mp4'];
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
				'video/x-flv'
	    	];
		}

		// initialize FileUploader
		$FileUploader = new FileUploader('media', array(
			'limit' => 1,
			'fileMaxSize' => floor($this->settings->file_size_allowed / 1024),
			'extensions' => $extensions,
			'title' => $file,
			'uploadDir' => $publicPath
		));

		// upload
		$upload = $FileUploader->upload();

		if ($upload['isSuccess']) {
			foreach($upload['files'] as $key=>$item) {
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
							$this->resizeImage($item['name'], $item['extension']);
						break;

					case 'video':
							$this->uploadVideo($item['name']);
						break;
				}
			}// foreach

		}// upload isSuccess

		return response()->json($upload);
	}

	/**
     * Resize image and add watermark
     *
     * @return void
     */
	protected function resizeImage($image, $extension)
	{
		$fileName = $image;
		$image = public_path('temp/').$image;
		$path = config('path.stories');
		$img  = Image::read($image);
		$url  = ucfirst(Helper::urlToDomain(url('/')));
		$username = auth()->user()->username;

		$width = $img->width();

		if ($extension == 'gif') {
			$this->insertImage($fileName);

			// Move file to Storage
			$this->moveFileStorage($fileName, $path);

		} else {
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

			// Insert in Database
			$this->insertImage($fileName);

			// Move file to Storage
			$this->moveFileStorage($fileName, $path);
		}

	 }// End method resizeImage

	 /**
	      * Insert Image to Database
	      *
	      * @return void
	      */
		  protected function insertImage($image)
		  {
			  MediaStories::create([
				'stories_id' => 0,
				'name' => $image,
				'type' => 'photo',
				'video_length' => '',
				'video_poster' => '',
				'created_at' => now()
			  ]);
 
		  }// end method insertImage

	/**
	 * Upload Video
	*
	* @return void
	*/
	protected function uploadVideo($video)
	{
		$path = config('path.stories');
		
		MediaStories::create([
			'stories_id' => 0,
			'name' => $video,
			'type' => 'video',
			'video_length' => '',
			'video_poster' => '',
			'created_at' => now()
		  ]);

		  // Move file to Storage
		  if ($this->settings->video_encoding == 'off') {
			$this->moveFileStorage($video, $path);
		}
	}

	/**
	 * Move file to Storage
	*
	* @return void
	*/
	protected function moveFileStorage($file, $path)
	{
		$localFile = public_path('temp/'.$file);
		
		// Move the file...
		Storage::putFileAs($path, new File($localFile), $file);
		
		// Delete temp file
		unlink($localFile);

	} // end method moveFileStorage

	/**
     * Delete a file
     *
     * @return void
     */
	public function delete()
	{
		// PATH
		$local = 'temp/';

		MediaStories::whereName($this->request->file)->delete();

		// Delete local file
		Storage::disk('default')->delete($local.$this->request->file);
		
		return response()->json([
        'success' => true
	 ]);
	}// End method

}
