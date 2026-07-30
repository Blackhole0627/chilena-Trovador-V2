<?php

namespace App\Http\Controllers;

use App\Helper;
use FileUploader;
use App\Models\Vault;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use App\Jobs\EncodeVideoVault;
use Illuminate\Http\JsonResponse;
use App\Services\CoconutVideoService;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Typography\FontFactory;

class UploadMediaVaultController extends Controller
{
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->path = config('path.vault');
    }

    public function store()
    {
        $publicPath = public_path('temp/');
        $file = strtolower(auth()->id() . uniqid() . time() . str_random(20));

        if (config('settings.video_encoding') == 'off') {
            $extensions = ['png', 'jpeg', 'jpg', 'gif', 'ief', 'video/mp4'];
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
        $fileUploader = new FileUploader('files', array(
            'limit' => config('settings.maximum_files_post'),
            'fileMaxSize' => floor(config('settings.file_size_allowed') / 1024),
            'extensions' => $extensions,
            'title' => $file,
            'uploadDir' => $publicPath
        ));

        $upload = $fileUploader->upload();

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
                }
            }
        }

        return json_encode($upload);
    }

    /**
     * Resize image and add watermark
     */
    protected function resizeImage($image): void
    {
        $fileName = $image['name'];
        $pathImage = public_path('temp/') . $fileName;
        $img = Image::read($pathImage);
        $url = ucfirst(Helper::urlToDomain(url('/')));
        $username = auth()->user()->username;

        $width = $img->width();
        $height = $img->height();

        if ($image['extension'] == 'gif') {
            $this->insertImage(
                fileName: $fileName,
                width: $width,
                height: $height,
                image: $image,
                imgType: 'gif'
            );

            // Move file to Storage
            $this->moveFileStorage($fileName, $this->path);
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
            $this->insertImage(
                fileName: $fileName,
                width: $width,
                height: $height,
                image: $image
            );

            // Move file to Storage
            $this->moveFileStorage($fileName, $this->path);
        }
    }

    protected function insertImage($fileName, $width, $height, $image, $imgType = null): void
    {
        Vault::create([
            'user_id' => auth()->id(),
            'type' => 'image',
            'file' => $fileName,
            'width' => $width,
            'height' => $height,
            'file_name' => $image['old_name'],
            'bytes' => $image['size'],
            'mime' => $image['type'],
            'img_type' => $imgType ?? '',
            'status' => 'active',
            'created_at' => now()
        ]);
    }

    protected function uploadVideo($videoItem): void
    {
        $video = Vault::create([
            'user_id' => auth()->id(),
            'type' => 'video',
            'file' => $videoItem['name'],
            'video_poster' => '',
            'file_name' => $videoItem['old_name'],
            'bytes' => $videoItem['size'],
            'mime' => $videoItem['type'],
            'status' => 'active',
            'created_at' => now()
        ]);

        // Move file to Storage 
        if (config('settings.video_encoding') == 'off') {
            $this->moveFileStorage($videoItem['name'], $this->path);
        } else {
            $this->encodeVideo($video);
        }
    }

    protected function encodeVideo($video): void
    {
        $video->update([
            'status' => 'pending'
        ]);

        if (config('settings.encoding_method') == 'ffmpeg') {
            dispatch(new EncodeVideoVault($video));
        } else {
            CoconutVideoService::handle($video, 'vault');
        }
    }

    protected function moveFileStorage($file, $path): void
    {
        $localFile = public_path('temp/' . $file);

        // Move the file...
        Storage::putFileAs($path, new File($localFile), $file);

        // Delete temp file
        unlink($localFile);
    }

    public function preload()
    {
        $media = Vault::whereUserId(auth()->id())->orderBy('id', 'asc')->paginate(2);
        $preloadedFiles = [];

        if ($media->count()) {
            foreach ($media as $file) {

                switch ($file->type) {
                    case 'image':
                        $pathFile = Helper::getFile(config('path.vault') . $file->file);
                        $name = $file->file_name;
                        break;

                    case 'video':
                        $pathFile = Helper::getFile(config('path.vault') . $file->file);
                        $name = $file->file_name;
                        break;
                }
                $preloadedFiles[] = [
                    'name' => $name,
                    'type' => $file->mime,
                    'size' => $file->bytes,
                    'file' => $pathFile,
                    'data' => [
                        'readerForce' => true,
                        'url' => $pathFile,
                        'date' => $file->created_at,
                        'listProps' => [
							'id' => $file->id,
						]
                    ],
                ];
            }
        }

        return json_encode($preloadedFiles);
    }

    public function delete(): JsonResponse
    {
        $path  = $this->path;
        $media = Vault::whereFile($this->request->name)->first();

        if ($media) {
            $localFile = 'temp/' . $media->file;

            Storage::delete($path . $media->file);
            Storage::delete($path . $media->video_poster);

            // Delete local file (if exist)
            Storage::disk('default')->delete($localFile);

            $media->delete();
        }

        return response()->json([
            'success' => true
        ]);
    }
}
