<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TeacherAssetController extends Controller
{
    public function upload(Request $request)
    {
        $data = $request->validate([
            'kind' => 'required|string|in:image,audio,video',
            'file' => 'required|file|max:51200',
        ]);

        $kind = $data['kind'];
        $file = $request->file('file');

        $extensionRules = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'audio' => ['mp3', 'wav', 'ogg', 'm4a'],
            'video' => ['mp4', 'webm', 'mov', 'm4v'],
        ];
        $mimeRules = [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'audio' => ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/mp4', 'audio/x-m4a'],
            'video' => ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-m4v'],
        ];

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($extension, $extensionRules[$kind], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Недопустимый формат файла для типа ' . $kind,
            ], 422);
        }

        $mimeType = strtolower((string) $file->getMimeType());
        if (!in_array($mimeType, $mimeRules[$kind], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Недопустимый MIME-тип файла для типа ' . $kind,
            ], 422);
        }

        $folder = public_path('uploads/teacher/' . $kind);
        File::ensureDirectoryExists($folder);

        $filename = uniqid($kind . '_', true) . '.' . $extension;
        $file->move($folder, $filename);

        return response()->json([
            'success' => true,
            'url' => asset('uploads/teacher/' . $kind . '/' . $filename),
            'kind' => $kind,
            'name' => $file->getClientOriginalName(),
        ]);
    }
}

