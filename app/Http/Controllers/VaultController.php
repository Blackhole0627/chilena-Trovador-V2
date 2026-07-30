<?php

namespace App\Http\Controllers;

use App\Helper;

class VaultController extends Controller
{
    public function show()
    {
        abort_if(auth()->user()->verified_id != 'yes' || !config('settings.allow_vault'), 404);

        $files = auth()->user()->vault()
            ->when(request('sort') == 'photos', function ($query) {
                $query->where('type', 'image');
            })
            ->when(request('sort') == 'videos', function ($query) {
                $query->where('type', 'video');
            })
            ->when(request('q') && strlen(request('q')) >= 3, function ($query) {
                $query->where('file_name', 'LIKE', '%' . request('q') . '%');
            })
            ->where('status', 'active')
            ->oldest()
            ->paginate(15);

        $preloadedFiles = [];

        if ($files->count()) {
            foreach ($files as $file) {

                $pathFile = Helper::getFile(config('path.vault') . $file->file);
                
                $preloadedFiles[] = [
                    'name' => $file->file_name,
                    'type' => $file->mime,
                    'size' => $file->bytes,
                    'file' => $pathFile,
                    'data' => [
                        'readerForce' => true,
                        'url' => $pathFile,
                        'date' => $file->created_at,
                        'listProps' => [
							'id' => $file->id,
                            'original' => $file->file,
						]
                    ],
                ];
            }
        }

        $preloadedFiles = $preloadedFiles ? json_encode($preloadedFiles) : false;

        return view('users.my-vault', [
            'files' => $files,
            'preloadedFiles' => $preloadedFiles
        ]);
    }

    public function getFiles()
    {
        $files = auth()->user()->vault()
            ->when(request('sort') == 'photos', function ($query) {
                $query->where('type', 'image');
            })
            ->when(request('sort') == 'videos', function ($query) {
                $query->where('type', 'video');
            })
            ->when(request('q') && strlen(request('q')) >= 3, function ($query) {
                $query->where('file_name', 'LIKE', '%' . request('q') . '%');
            })
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('includes.vault-files', ['files' => $files])->render();
    }
}
