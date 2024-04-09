<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tool;
use App\Models\Tag;

class DashboardController extends Controller
{
    public function getStats()
    {
        $toolCount = Tool::count();
        $tagCount = Tag::count();

        return response()->json([
            'tools' => $toolCount,
            'tags' => $tagCount,
        ]);
    }
}
