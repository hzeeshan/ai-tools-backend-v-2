<?php

namespace App\Services;

use App\Models\App;
use App\Models\AppImage;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AppService
{
    public function index($request)
    {

        $licenseTypes = explode(",", strtolower($request->get('license_type', '')));

        // If "all" is one of the license types or license type is empty, return all apps
        if (in_array('all', $licenseTypes) || empty($licenseTypes[0])) {
            return App::with('main_image', 'license_type')->latest()->paginate(12);
        }

        // Otherwise, filter by the provided license types
        return App::whereHas('license_type', function ($query) use ($licenseTypes) {
            $query->whereIn('name', $licenseTypes);
        })->with('main_image', 'license_type')->latest()->paginate(12);
    }

    public function search($request)
    {
        $searchTerm = $request->query('search');
        $apps = App::query()
            ->select('id', 'name', 'slug', 'app_image_id')
            ->where('name', 'LIKE', "%{$searchTerm}%")
            ->orWhere('description', 'LIKE', "%{$searchTerm}%")
            ->with(['main_image' => function ($query) {
                $query->select('id', 'path');
            }])
            ->get();

        return response()->json($apps);
    }

    public function show($request)
    {
        $app = App::with('platform', 'main_image', 'category')->where('slug', $request->slug)->first();

        // If the app has price plans, decode them
        if ($app && $app->price_plans) {
            $app->price_plans = json_decode($app->price_plans);
        }

        return $app;
    }

    public function getAppsByCategory($categoryId, $count = 3)
    {
        // Fetch the category.
        $category = Category::find($categoryId);

        // If category doesn't exist, return an appropriate response.
        if (!$category) {
            return response()->json(['message' => 'Category not found.'], 404);
        }

        // Fetch the apps of this category.
        $apps = $category->apps()->with('main_image')->take($count)->get();

        // If the category has no apps, return an appropriate response.
        if ($apps->isEmpty()) {
            return response()->json(['message' => 'No apps found for this category.'], 404);
        }

        return response()->json($apps);
    }

    public function filterAppsByPlatform($request)
    {
        $selectedPlatform = $request->id;
        $apps = App::whereIn('platform_id', $selectedPlatform)
            ->with('main_image')
            ->latest()
            ->paginate(12);

        return $apps;
    }
    public function filterAppsByCategory($request)
    {
        $categoryIds = explode(',', $request->get('category_ids'));

        $apps = App::whereIn('category_id', $categoryIds)
            ->with('main_image')
            ->paginate(20);
        return $apps;
    }

    public function filterAppsByTags($request)
    {
        $tagIds = $request->tagIds;

        $apps = [];

        // Check if tagIds is provided and is an array
        if (!$tagIds || !is_array($tagIds)) {
            return response()->json($apps);
        }

        // Fetch apps that have any of the provided tags
        $apps = App::whereHas('tags', function ($query) use ($tagIds) {
            $query->whereIn('tags.id', $tagIds);
        })
            ->with('main_image')
            ->take(6)
            ->get();

        return response()->json($apps);
    }


    public function store(Request $request, array $validated)
    {

        try {
            // Create a new app
            $app = App::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['slug'], '-'),
                'short_description' => $validated['short_description'],
                'description' => $request->description,
                'platform_id' => $validated['platform_id'],
                'category_id' => $request->category_id,
                'website_link' => $request->website_link,
                'license_type_id' => $request->license_type_id,
            ]);

            // If main image is present, upload it and set it as main image
            if ($request->hasFile('mainImage')) {
                DB::transaction(function () use ($request, $app) {
                    $image = $this->uploadImage($request->file('mainImage'), $app->id, 'main');
                    $app->app_image_id = $image->id;
                    $app->save();
                });
            }

            // If other images are present, upload them
            if ($request->hasFile('otherImages')) {
                DB::transaction(function () use ($request, $app) {
                    foreach ($request->file('otherImages') as $file) {
                        $this->uploadImage($file, $app->id, 'other');
                    }
                });
            }

            // Create price plans
            $pricePlansData = json_decode($validated['price_plans'], true);
            foreach ($pricePlansData as $pricePlanData) {
                $app->price_plans()->create($pricePlanData);
            }

            // If the app has tags, sync them
            if ($request->existingTags || $request->newTags) {
                $existingTagIds = json_decode($request->existingTags, true);
                $newTags = json_decode($request->newTags, true);

                // Create new tags and get their IDs
                foreach ($newTags as $tagName) {
                    $newTag = Tag::firstOrCreate(['name' => $tagName]);
                    $existingTagIds[] = $newTag->id;
                }

                $app->tags()->sync($existingTagIds);
            }

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => 'App successfully created',
                'data' => $app
            ], 201);
        } catch (\Exception $e) {

            \Log::error($e->getMessage());

            return response([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function uploadImage($file, $appId, $type = null)
    {
        $filename = Str::uuid() . '.webp'; // change extension to webp

        $image = Image::make($file);

        $width = ($type == 'main') ? 1200 : 800;
        $quality = ($type == 'main') ? 80 : 70;

        $image->resize($width, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();  // this prevents the image from being upscaled
        });

        // Compress image (you can adjust the quality as per your needs)
        $image->encode('webp', $quality);


        // Save the image
        Storage::disk('public')->put("images/{$filename}", (string) $image);

        // Set the URL path
        $urlPath = 'storage/images/' . $filename;

        return AppImage::create([
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.webp',
            'path' => $urlPath,
            'app_id' => $appId
        ]);
    }

    public function update(Request $request, $id)
    {
        $app = $this->updateApp($request, $id);
        $this->updatePricePlans($request->get('price_plans'), $app);
        $this->updateTags($request, $app);
        $this->updateImages($request, $id);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'App successfully updated',
            'data' => $app
        ], 200);
    }

    private function updateApp($request, $id)
    {
        $app = App::findOrFail($id);
        $app->name = $request->name;
        $app->slug = Str::slug($request->slug, '-');
        $app->short_description = $request->short_description;
        $app->description = $request->description;
        $app->platform_id = $request->platform_id;
        $app->category_id = $request->category_id;
        $app->website_link = $request->website_link;
        $app->license_type_id = $request->license_type_id;
        $app->save();

        return $app;
    }

    private function updatePricePlans($plans, $app)
    {
        $pricePlansData = json_decode($plans, true);

        // Get all the existing plan ids from the request
        $planIds = array_filter(array_column($pricePlansData, 'id'));

        // Delete any plans that are not included in the request
        $app->price_plans()->whereNotIn('id', $planIds)->delete();

        // Update or create plans
        foreach ($pricePlansData as $pricePlanData) {
            // If id exists, update or create, else create a new one
            if (isset($pricePlanData['id'])) {
                $app->price_plans()->updateOrCreate(
                    ['id' => $pricePlanData['id']],
                    ['name' => $pricePlanData['name'], 'price' => $pricePlanData['price'], 'app_id' => $app->id]
                );
            } else {
                $app->price_plans()->create(
                    ['name' => $pricePlanData['name'], 'price' => $pricePlanData['price'], 'app_id' => $app->id]
                );
            }
        }
    }

    private function updateImages($request, $id)
    {
        if ($request->mainImage) {

            // If originalMainImage and formData.mainImage are different, replace the main image
            if ($request->originalMainImage != $request->mainImage->getClientOriginalName()) {
                // Delete the original main image
                /* $originalImage = Image::find($request->originalMainImage);
                if ($originalImage) {
                    $this->deleteImage($originalImage);
                } */

                // Save the new one
                $uploadedImage = $this->uploadImage($request->mainImage, $id);

                $app = App::find($id);
                $app->app_image_id = $uploadedImage->id;
                $app->save();
            }
        }

        // If there are any differences between originalOtherImages and formData.otherImages, 
        // Delete the removed ones and save the new ones
        $originalOtherImages = json_decode($request->originalOtherImages);

        if ($request->otherImages) {
            /* foreach ($originalOtherImages as $imageId) {
                $image = AppImage::find($imageId);
                if ($image) {

                    if (!in_array($image->title, array_column($request->otherImages, 'getClientOriginalName'))) {
                        // Delete the image
                        $this->deleteImage($image);
                    }
                }
            } */
            foreach ($request->otherImages as $image) {
                $this->uploadImage($image, $id);
            }
        }
    }

    private function deleteImage(Image $image)
    {
        // Delete the image file from storage
        Storage::delete($image->path);

        // Delete the Image model instance
        $image->delete();
    }

    private function updateTags($request, $app)
    {
        $existingTagIds = json_decode($request->existingTags, true);
        $newTags = json_decode($request->newTags, true);

        // Create new tags and get their IDs
        foreach ($newTags as $tagName) {
            $newTag = Tag::firstOrCreate(['name' => $tagName]);
            $existingTagIds[] = $newTag->id;
        }

        $app->tags()->sync($existingTagIds);
    }


    public function delete($appId)
    {
        try {
            App::find($appId)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getSingleAppDetail($appId)
    {
        return App::with('platform', 'category', 'main_image', 'images', 'license_type', 'price_plans', 'tags')
            ->findOrFail($appId);
    }
}
