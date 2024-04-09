<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    public function index()
    {
        return $this->tagService->index();
    }

    public function getToolsByTagSlug($slug)
    {
        return $this->tagService->getToolsByTagSlug($slug);
    }

    public function store(Request $request)
    {
        return $this->tagService->store($request);
    }

    public function update(Request $request)
    {
        return $this->tagService->update($request);
    }

    public function destroy($id)
    {
        return $this->tagService->destroy($id);
    }
}
