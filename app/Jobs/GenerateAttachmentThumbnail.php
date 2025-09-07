<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class GenerateAttachmentThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     */
    public function __construct(public $attachment)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $att = $this->attachment->fresh();
        if (!$att || !str_starts_with($att->mime, 'image/'))
            return;

        $disk = $att->disk;
        $imgData = Storage::disk($disk)->get($att->path);

        $manager = new ImageManager(new Driver());
        $image = $manager->read($imgData)->scaleDown(400, 400);

        $thumbPath = preg_replace('/(\.[^.]+)?$/', '_thumb.jpg', $att->path);
        Storage::disk($disk)->put($thumbPath, (string) $image->toJpeg(80));

        $att->update(['thumbnail_path' => $thumbPath]);
    }
}
