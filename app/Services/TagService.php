<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Tool;

class TagService
{
    public function index()
    {
        return Tag::all();
    }

    public function getToolsByTagSlug($slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $tools = Tool::with('tags')
            ->whereHas('tags', function ($query) use ($tag) {
                $query->where('tags.id', $tag->id);
            })
            ->paginate(10);

        return response()->json($tools);
    }
}
