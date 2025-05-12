<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Traits\HasApiResponses;
use App\Models\Conference;
use App\Models\Page;
use App\Models\User;
use App\Models\FileUpload;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use HasApiResponses;

    /**
     * Get dashboard statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function getStats()
    {
        $stats = [
            'conferences' => Conference::count(),
            'pages' => Page::count(),
            'users' => User::count(),
            'files' => FileUpload::count(),
        ];
        
        return $this->successResponse($stats);
    }
}
