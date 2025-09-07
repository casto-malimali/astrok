<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Jobs\GenerateAttachmentThumbnail;
use App\Models\Attachment;
use Illuminate\Support\Facades\URL;


class AttachmentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Note $note)
    {
        $this->authorize('update', $note);

        $data = $request->validate([
            'file' => 'required|file|max:5120|mimes:jpg,jpeg,png,webp,pdf,txt',
        ]);

        $file = $data['file'];
        $disk = 'private';
        $dir = 'notes/' . $note->id . '/' . now()->format('Y/m');

        $path = $file->store($dir, $disk);

        $att = $note->attachments()->create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            // prefer getMimeType() with fallback to client mime
            'mime' => $file->getMimeType() ?? $file->getClientMimeType() ?? null,
            'size' => $file->getSize(),
        ]);

        // --- Robust image detection (covers fakes & different servers) ---
        $mime = strtolower($file->getMimeType() ?? $file->getClientMimeType() ?? '');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        $isImage = str_starts_with($mime, 'image/')
            || in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);

        if ($isImage) {
            GenerateAttachmentThumbnail::dispatch($att);
        }

        return response()->json([
            'id' => $att->id,
            'download_url' => url()->temporarySignedRoute(
                'attachments.download',
                now()->addMinutes(10),
                ['attachment' => $att->id]
            ),
        ], 201);
    }

    // public function download(Attachment $attachment, Request $request)
    // {
    //     $this->authorize('view', $attachment->note);

    //     return Storage::disk($attachment->disk)->download(
    //         $attachment->path,
    //         $attachment->original_name,
    //     );

    // }
    public function download(Attachment $attachment, Request $request)
    {
        $this->authorize('view', $attachment->note);

        $disk = $attachment->disk;
        $path = $attachment->path;

        abort_unless(Storage::disk($disk)->exists($path), 404);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $fs */
        $fs = Storage::disk($disk); // docblock makes Intelephense recognize the type

        // This returns a StreamedResponse with proper headers
        return $fs->download($path, $attachment->original_name);
    }


    public function destroy(Attachment $attachment)
    {
        $this->authorize('update', $attachment->note);

        Storage::disk($attachment->disk)->delete($attachment->path);
        if ($attachment->thumb_path) {
            Storage::disk($attachment->disk)->delete($attachment->thumb_path);
        }
        $attachment->delete();

        return response()->noContent();
    }
    //Helper to generate signed download URL
    protected function signedDownloadUrl(Attachment $att): string
    {
        return URL::temporarySignedRoute(
            'attachments.download',
            now()->addMinutes(10),
            ['attachment' => $att->id]
        );
    }
}
