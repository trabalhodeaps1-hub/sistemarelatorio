<?php

namespace App\Http\Controllers;

use App\Models\Foto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Serve as fotos do disco privado, sempre passando pela FotoPolicy. */
class FotoController extends Controller
{
    public function show(Foto $foto): StreamedResponse
    {
        $this->authorize('view', $foto);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($foto->path), 404);

        return $disk->response($foto->path, basename($foto->path), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
