<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'type',
        'content',
        'page_id',
        'order',
        'settings',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'settings' => 'array',
        'order' => 'integer'
    ];

    /**
     * Get the page that owns the content.
     */
    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Get the user who created the content.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the content.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all of the content's files.
     */
    public function files()
    {
        return $this->morphMany(FileUpload::class, 'uploadable');
    }
}
