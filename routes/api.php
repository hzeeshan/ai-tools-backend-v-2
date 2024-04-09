<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TagController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\DashboardController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('logged-in-user', [UserController::class, 'loggedInUser']);
});



/* disable CSRF for these routes  */
Route::group(['middleware' => 'api', 'prefix' => 'public'], function () {

    Route::post('/filter-apps/platform', [AppController::class, 'filterAppsByPlatform']);
    Route::post('/store-app', [ToolController::class, 'store'])->name('app.store');
    Route::post('/app/{appId}', [ToolController::class, 'update'])->name('app.update');
    Route::delete('/delete-tool/{toolId}', [ToolController::class, 'delete'])->name('app.delete');

    Route::post('/rating/store', [RatingController::class, 'store']);

    Route::delete('/image/{id}', [AppImageController::class, 'destroy'])->name('image.delete');

    Route::delete('/tag/{id}', [TagController::class, 'destroy'])->name('tag.delete');

    Route::post('/contact-form', [ContactFormSubmissionController::class, 'store']);
});


Route::get('/tool/{toolId}', [ToolController::class, 'getSingleAppDetail'])->where('toolId', '[0-9]+');
Route::get("/tool/{slug}", [ToolController::class, 'getToolBySlug']);
Route::get("/tools", [ToolController::class, 'index']);
Route::get("/tools/search", [ToolController::class, 'searchTool']);
Route::get('/tools/by-tags', [ToolController::class, 'getToolsByTagIds']);
Route::delete('/tag/delete/{id}', [TagController::class, 'destroy'])->name('tag.delete');

Route::get('/tags', [TagController::class, 'index']);
Route::get('/tags/{slug}/tools', [TagController::class, 'getToolsByTagSlug']);
Route::post('/tag/create', [TagController::class, 'store']);
Route::post('/tag/update', [TagController::class, 'update']);

Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
