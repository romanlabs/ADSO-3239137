<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\LaptopPhotoService;
use App\Domain\Exceptions\ValidationFailed;
use App\Infrastructure\Http\Presenters;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

/**
 * CA-03.4: PUT, DELETE and GET of /laptops/{id}/photo. The type is checked by content inside the service, never by
 * the extension or the Content-Type the client sends.
 */
final readonly class LaptopPhotoController
{
    public function __construct(private LaptopPhotoService $photos)
    {
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            throw ValidationFailed::field('file', 'obligatorio, imagen JPG o PNG de máximo 2 MB');
        }

        $laptop = $this->photos->attach($id, (string) file_get_contents($file->getRealPath()));

        return new JsonResponse(Presenters::laptop($laptop), 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function destroy(string $id): Response
    {
        $this->photos->detach($id);

        return new Response(null, 204);
    }

    public function show(string $id): Response
    {
        $photo = $this->photos->read($id);

        return new Response($photo->bytes, 200, [
            'Content-Type' => $photo->mimeType,
            'Content-Length' => (string) strlen($photo->bytes),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
