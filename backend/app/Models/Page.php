<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'meta_description',
        'layout',
        'is_published',
        'conference_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Get the conference that owns the page.
     */
    public function conference()
    {
        return $this->belongsTo(Conference::class);
    }

    /**
     * Get the contents for the page.
     */
    public function contents()
    {
        return $this->hasMany(Content::class)->orderBy('order');
    }

    /**
     * Get the user who created the page.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the page.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
