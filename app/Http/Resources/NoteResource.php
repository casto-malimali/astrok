<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'tags' => $this->whenLoaded('tags', fn() => $this->tags->pluck('name')->values()),
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($a) {
                    return [
                        'id' => $a->id,
                        'name' => $a->original_name,
                        'mime' => $a->mime,
                        'size' => $a->size,
                        'thumb_exists' => !is_null($a->thumb_path),
                        // Generate a short-lived signed URL (10 min) per attachment
                        'download_url' => url()->temporarySignedRoute(
                            'attachments.download',
                            now()->addMinutes(10),
                            ['attachment' => $a->id]
                        )


                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
