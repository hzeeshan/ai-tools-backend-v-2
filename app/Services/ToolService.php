<?php

namespace App\Services;

use App\Models\Tool;

class ToolService
{

    public function index($request)
    {
        $page = $request->get('page', 1);
        $pageSize = 10;
        //sleep(3);
        $tools = Tool::with('tags')->skip(($page - 1) * $pageSize)->take($pageSize)->get();
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
}
