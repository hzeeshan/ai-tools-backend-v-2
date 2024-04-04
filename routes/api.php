<?php

use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\UserController;


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
});

Route::get("/tools", [ToolController::class, 'index']);
Route::get("/tools/search", [ToolController::class, 'searchTool']);
Route::get("/tool/{slug}", [ToolController::class, 'getToolBySlug']);
Route::get('/tags/{slug}/tools', [TagController::class, 'getToolsByTagSlug']);


Route::get('/tags', [TagController::class, 'index']);
