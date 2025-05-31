<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Conference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'start_date',
        'end_date',
        'location',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['editors'];

    /**
     * Get the editors that manage the conference.
     */
    public function editors()
    {
        return $this->belongsToMany(User::class, 'editor_conferences')
            ->withTimestamps();
    }

    /**
     * Get the pages for the conference.
     */
    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Get all published pages for the conference.
     */
    public function publishedPages()
    {
        return $this->pages()->where('status', 'published');
    }

    /**
     * Get the conference with all its related data.
     *
     * @return \App\Models\Conference
     */
    public function loadWithRelations()
    {
        return Cache::remember('conference_' . $this->id . '_with_relations', 3600, function () {
            return $this->load([
                'editors',
                'pages' => function ($query) {
                    $query->where('status', 'published')
                        ->orderBy('created_at', 'desc');
                }
            ]);
        });
    }

    /**
     * Scope a query to only include published conferences.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include upcoming conferences.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())
            ->orderBy('start_date', 'asc');
    }
}
