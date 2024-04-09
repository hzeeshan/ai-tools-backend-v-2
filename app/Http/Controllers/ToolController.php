<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use Illuminate\Http\Request;
use App\Services\ToolService;
use App\Http\Requests\StoreAppRequest;

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

    public function store(Request $request)
    {
        //$validated = $request->validated();
        return $this->toolService->store($request);
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

    public function getSingleAppDetail($toolId)
    {
        return $this->toolService->getSingleAppDetail($toolId);
    }

    public function update(Request $request, $id)
    {
        return $this->toolService->update($request, $id);
    }

    public function delete($toolId)
    {
        return $this->toolService->destroy($toolId);
    }
}
