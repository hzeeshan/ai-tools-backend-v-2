<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Tool;
use Illuminate\Support\Str;

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

    public function store($request)
    {
        try {
            Tag::create([
                'name' => $request->tagName,
                'slug' => Str::slug($request->tagName),
            ]);
            return response()->json(['success' => true, 'message' => 'Tag created successfully']);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update($request)
    {
        $tagId = $request->tagId;
        $tagName = $request->tagName;

        try {
            $tag = Tag::findOrFail($tagId);
            $tag->update(['name' => $tagName, 'slug' => Str::slug($tagName)]);

            return response()->json(['success' => true, 'message' => 'Tag updated successfully']);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $tag = Tag::findOrFail($id);
            $tag->delete();

            return response()->json(['success' => true, 'message' => 'Tag deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
