<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\Category;
use App\Services\AppService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAppRequest;

class AppController extends Controller
{
    public $appService;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
    }

    public function index(Request $request)
    {
        return $this->appService->index($request);
    }

    public function search(Request $request)
    {
        return $this->appService->search($request);
    }

    public function show(Request $request)
    {
        return $this->appService->show($request);
    }

    public function getAppsByCategory($categoryId, $count = 3)
    {
        return $this->appService->getAppsByCategory($categoryId, $count = 3);
    }

    public function filterAppsByPlatform(Request $request)
    {
        return $this->appService->filterAppsByPlatform($request);
    }

    public function filterAppsByCategory(Request $request)
    {
        return $this->appService->filterAppsByCategory($request);
    }
    public function filterAppsByTags(Request $request)
    {
        return $this->appService->filterAppsByTags($request);
    }

    public function store(StoreAppRequest $request)
    {
        $validated = $request->validated();
        return $this->appService->store($request, $validated);
    }
    public function update(Request $request, $id)
    {
        return $this->appService->update($request, $id);
    }

    public function delete($appId)
    {
        return $this->appService->delete($appId);
    }

    public function getSingleAppDetail($appId)
    {
        return $this->appService->getSingleAppDetail($appId);
    }
}
