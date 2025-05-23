<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conference;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class ConferencePageController extends Controller
{
    public function __construct()
    {
        // No middleware needed for public access
    }

    public function show(Conference $conference): JsonResponse
    {
        $pages = $conference->pages()
            ->where('is_published', true)
            ->orderBy('order')
            ->get();

        return response()->json([
            'conference' => $conference,
            'pages' => $pages
        ]);
    }
} 