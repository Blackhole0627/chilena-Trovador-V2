<?php

namespace App\Http\Controllers;

use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReelPreviewController extends Controller
{
    public function uploadPreview(Request $request)
    {
        try {
            $pathTemp = 'temp/';
            $previewImage = $request->file('preview_image');
            $previewFileName = $this->generatePreviewFileName();

            Storage::disk('default')->putFileAs($pathTemp, new File($previewImage), $previewFileName, 'public');       

            return response()->json([
                'success' => true,
                'message' => 'Successfully generated preview',
                'data' => [
                    'preview_filename' => $previewFileName,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error in validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing preview: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generatePreviewFileName()
    {
        return str_random(20) . uniqid() . now()->timestamp . '-poster.jpg';
    }
}
