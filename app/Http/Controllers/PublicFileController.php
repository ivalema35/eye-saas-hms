<?php

namespace App\Http\Controllers;

use App\Support\PublicStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicFileController extends Controller
{
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $normalized = PublicStorage::normalizePath($path);

        if ($normalized === null || ! Storage::disk('public')->exists($normalized)) {
            abort(404);
        }

        $absolute = storage_path('app/public/'.$normalized);
        $mime = mime_content_type($absolute) ?: 'application/octet-stream';

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
