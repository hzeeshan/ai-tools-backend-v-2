<?php

namespace App\Http\Controllers;

use App\Models\ToolRating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request)
    {

        ToolRating::create([
            'user_id' => $request->userId,
            'tool_id' => $request->toolId,
            'rating' => $request->rating,
            'comment' => $request->review
        ]);

        return response()->json(['success' => true, 'message' => 'Review created successfully']);
    }
}
