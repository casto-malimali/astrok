<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'path',
        'thumb_path',
        'original_name',
        'mime',
        'size',
    ];

    public function note()
    {
        return $this->belongsTo(Note::class);
    }
}
