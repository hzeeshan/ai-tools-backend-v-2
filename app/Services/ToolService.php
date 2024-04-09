<?php

namespace App\Services;

use Image;
use App\Models\Tag;
use App\Models\Tool;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ToolService
{


    public function index($request)
    {
        $pageSize = 12;
        $tools = Tool::with('tags')
            ->orderBy('favorites_count', 'desc')
            ->paginate($pageSize);

        return response()->json($tools);
    }


    public function searchTool($request)
    {

        $baseQuery = Tool::query();

        $this->applyPricingFilter($baseQuery, $request);
        $this->applyFeatureFilter($baseQuery, $request);
        $this->applySortByFilter($baseQuery, $request);

        if ($request->has('query') && !empty($request->input('query'))) {
            $this->performSearch($baseQuery, $request->input('query'));
        }

        $perPage = $request->get('perPage', 50);

        $results = $baseQuery->paginate($perPage);

        return response()->json($results);
    }

    public function store($request)
    {

        try {

            $tool = Tool::create([
                'name' => $request->name,
                'slug' => Str::slug($request->slug, '-'),
                'short_description' => $request->short_description,
                'long_description' => $request->description,
                'tool_link' => $request->tool_link,
                'price' => $request->price,
                'pricing_plans' => $request->planType,
                'is_verified' => $request->is_verified == 'true' ? 1 : 0
            ]);

            if ($request->hasFile('mainImage')) {
                DB::transaction(function () use ($request, $tool) {
                    $imagePath = $this->uploadImage($request->file('mainImage'), $tool->id, 'main');
                    $tool->image_path = $imagePath;
                    $tool->save();
                });
            }

            $this->handleTags($request, $tool);

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => 'App successfully created',
                'data' => $tool
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
            $constraint->upsize();
        });

        // Compress image (you can adjust the quality as per your needs)
        $image->encode('webp', $quality);


        // Save the image
        Storage::disk('public')->put("images/{$filename}", (string) $image);

        // Set the URL path
        $urlPath = 'storage/images/' . $filename;

        return $urlPath;
    }

    private function handleTags($request, $tool)
    {
        $existingTagIds = $request->existingTags ? json_decode($request->existingTags, true) : [];
        $newTags = $request->newTags ? json_decode($request->newTags, true) : [];

        foreach ($newTags as $tagName) {
            $slug = $this->generateUniqueSlugForTag($tagName);
            $newTag = Tag::firstOrCreate(['name' => $tagName, 'slug' => $slug]);
            $existingTagIds[] = $newTag->id;
        }

        $tool->tags()->sync($existingTagIds);
    }

    private function generateUniqueSlugForTag($tagName)
    {
        $slug = Str::slug($tagName);
        for ($suffix = 1; Tag::where('slug', $slug)->exists(); $suffix++) {
            $slug = Str::slug($tagName) . '-' . $suffix;
        }
        return $slug;
    }

    private function applyPricingFilter($query, $request)
    {
        if ($request->has('pricing')) {
            $pricingKey = $request->input('pricing');

            // Create a mapping for pricing
            $pricingMapping = [
                'free' => 'Free',
                'freemium' => 'Freemium',
                'freeTrial' => 'Free Trial',
                'paid' => 'Paid',
                'contactForPricing' => 'Contact for Pricing',
                'deals' => 'Deals',
            ];

            $pricingValue = $pricingMapping[$pricingKey] ?? null;

            if ($pricingValue) {
                $query->where('pricing_plans', $pricingValue);
            }
        }
    }

    private function applyFeatureFilter($query, $request)
    {
        if ($request->has('feature')) {
            $featureKey = $request->input('feature');

            // Create a mapping
            $featureMapping = [
                'waitlist' => 'Waitlist',
                'openSource' => 'Open Source',
                'mobileApp' => 'Mobile App',
                'discordCommunity' => 'Discord Community',
                'noSignupRequired' => 'No Signup Required',
                'api' => 'API',
                'browserExtension' => 'Browser Extension',
            ];

            // Translate the feature key into the actual database string
            $featureValue = $featureMapping[$featureKey] ?? null;
            if ($featureValue) {
                $query->whereJsonContains('other_features', $featureValue);
            }
        }
    }

    private function applySortByFilter($query, $request)
    {
        if ($request->has('sortBy')) {
            $sortBy = $request->input('sortBy');
            switch ($sortBy) {
                case 'new':
                    $query->orderBy('created_at', 'desc');
                    break;

                case 'verified':
                    $query->orderBy('is_verified', 'desc');
                    break;

                case 'popular':
                    $query->orderBy('favorites_count', 'desc');
                    break;

                default:
                    $query->orderBy('is_verified', 'desc');
                    break;
            }
        }
    }

    private function performSearch($baseQuery, $searchTerm)
    {
        $baseQuery->where(function ($query) use ($searchTerm) {
            $query->where('name', 'like', '%' . $searchTerm . '%')
                ->orWhere('short_description', 'like', '%' . $searchTerm . '%')
                ->orWhere('long_description', 'like', '%' . $searchTerm . '%')
                ->orWhereHas('tags', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%' . $searchTerm . '%');
                });
        });
    }

    public function getToolBySlug($slug)
    {
        $tool = Tool::with(['tags', 'socialLinks'])->where('slug', $slug)->firstOrFail();

        // Getting related tools
        $tagIds = $tool->tags->pluck('id')->toArray(); // get IDs of tags of the current tool

        $relatedTools = Tool::with('tags')
            ->whereHas('tags', function ($query) use ($tagIds) {
                $query->whereIn('tags.id', $tagIds);  // Specify the table name here
            })
            ->where('slug', '<>', $slug) // Exclude the current tool
            ->take(9)
            ->get();

        return response()->json([
            'tool' => $tool,
            'relatedTools' => $relatedTools,
        ]);
    }

    public function getToolsByTagIds($request)
    {
        $tagIdsString = $request->query('tagIds');
        $tagIds = explode(',', $tagIdsString);

        if (empty(array_filter($tagIds))) {
            return response()->json(['error' => 'Invalid tagIds parameter'], 400);
        }

        $tools = Tool::whereHas('tags', function ($query) use ($tagIds) {
            $query->whereIn('tags.id', $tagIds);
        })->paginate(50);

        return response()->json($tools);
    }

    public function getSingleAppDetail($toolId)
    {
        return Tool::with('tags', 'socialLinks')->findOrFail($toolId);
    }

    public function update($request, $id)
    {

        $tool = $this->updateApp($request, $id);
        $this->handleTags($request, $tool);
        $this->updateImages($request, $id);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'App successfully updated',
            'data' => $tool
        ], 200);
    }

    private function updateApp($request, $id)
    {
        $app = Tool::findOrFail($id);
        $app->name = $request->name;
        $app->slug = Str::slug($request->slug, '-');
        $app->short_description = $request->short_description;
        $app->long_description = $request->description;
        $app->tool_link = $request->tool_link;
        $app->is_verified = $request->is_verified == 'true' ? 1 : 0;
        $app->save();

        return $app;
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
                $imagePath = $this->uploadImage($request->mainImage, $id);

                $app = Tool::find($id);
                $app->image_path = $imagePath;
                $app->save();
            }
        }
    }

    public function destroy($toolId)
    {

        try {
            Tool::findOrFail($toolId)->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
