<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use Illuminate\Http\Request;
use App\Services\ToolService;

class ToolController extends Controller
{
    public $toolService;

    public function __construct(ToolService $toolService)
    {
        $this->toolService = $toolService;
    }

    public function index(Request $request)
    {
        return $this->toolService->index($request);
    }

    public function getToolBySlug($slug)
    {
        return $this->toolService->getToolBySlug($slug);
    }

    public function searchTool(Request $request)
    {
        return $this->toolService->searchTool($request);
    }

    public function getToolsByTagIds(Request $request)
    {
        return $this->toolService->getToolsByTagIds($request);
    }
}
