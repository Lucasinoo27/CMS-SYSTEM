<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileUpload extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'path',
        'disk',
        'created_by'
    ];

    /**
     * Get the parent uploadable model.
     */
    public function uploadable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created the file.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the full URL to the file.
     */
    public function getUrlAttribute()
    {
        return \Storage::disk($this->disk)->url($this->path);
    }
}
