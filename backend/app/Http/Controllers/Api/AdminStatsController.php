<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\User;
use App\Models\Page;
use App\Models\Conference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminStatsController extends Controller
{
    use HasApiResponses;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        
        $this->middleware(function ($request, $next) {
            if (!auth()->user()?->isAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            return $next($request);
        });
    }

    public function getStats()
    {
        $stats = Cache::remember('admin.stats', 300, function () {
            return [
                'total_users' => User::count(),
                'total_pages' => Page::count(),
                'total_conferences' => Conference::count(),
                'published_pages' => Page::where('is_published', true)->count(),
                'unpublished_pages' => Page::where('is_published', false)->count(),
            ];
        });

        return $this->successResponse($stats);
    }
} 