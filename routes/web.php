<?php


use App\Models\Tag;
use App\Models\Tool;
use Illuminate\Support\Str;
use App\Models\ToolSocialLink;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\UserController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/assing-role', [UserController::class, 'assignAdminRole']);
Route::get('/dev', function () {
    return;
    $csvFile = '/Users/hafizzeeshanriaz/dev/python/selenium/data.csv';
    $data = array_map('str_getcsv', file($csvFile));
    $headers = array_shift($data);

    $socialTypeMapping = [
        'linkedin.com' => 'LinkedIn',
        'twitter.com' => 'Twitter',
        'facebook.com' => 'Facebook',
        'youtube.com' => 'YouTube',
        'instagram.com' => 'Instagram',
        'chrome.google.com' => 'Chrome Extension',
        'github.com' => 'GitHub',
        'mailto:' => 'Email',
        'discord.com' => 'Discord',
    ];

    $tagType = "Text";

    /* foreach ($data as $index => $row) {
        if (count($headers) != count($row)) {
            echo "Row number " . ($index + 2) . " has a mismatched count.<br>";
        }
    }

    return 'Success'; */


    //dd($headers);
    foreach ($data as $row) {
        if (count($headers) != count($row)) {
            continue;
        }

        $row = array_combine($headers, $row);
        $otherFunctionalities = json_decode(str_replace("'", '"', $row['Other functionalities']));
        $socialLinks = json_decode(str_replace("'", '"', $row['Social link']));
        // Handle categories
        $tags = json_decode(str_replace("'", '"', $row['Related Categories']));

        $tool = Tool::firstOrCreate(
            ['slug' => $row['Slug']],
            [
                'name' => $row['Name'],
                'short_description' => $row['Short Description'],
                'long_description' => $row['Long Description'],
                'tool_link' => $row['Tool Link'],
                'price' => $row['Price'] == '' ? null : $row['Price'],
                'pricing_plans' => $row['Pricing Plans'],
                'is_verified' => ($row['Is Verified'] == 'Not verified') ? 0 : 1,
                'other_features' => empty($otherFunctionalities) ? null : json_encode($otherFunctionalities),
                'favorites_count' => $row['Favorites Count'] == '' ? 0 : (int)$row['Favorites Count'],
            ]
        );

        // Only download and save the image if the tool was just created.
        if ($tool->wasRecentlyCreated) {
            $imageUrl = "https://www.futurepedia.io" . $row['Image Url'];
            try {
                $response = Http::get($imageUrl);

                if ($response->successful()) {
                    $filename = Str::uuid() . '.webp';
                    Storage::disk('public')->put("images/{$filename}", $response->body());
                    $urlPath = 'storage/images/' . $filename;

                    // Update the tool's image path in the database.
                    $tool->image_path = $urlPath;
                    $tool->save();
                }
            } catch (\Exception $e) {
                // Handle exception or just ignore if you wish to not update the image path.
            }
        }

        // Check if there are any social links for this tool.
        if (!empty($socialLinks)) {
            foreach ($socialLinks as $link) {
                $type = null;

                // Check against our mapping to determine the type.
                foreach ($socialTypeMapping as $domain => $domainType) {
                    if (strpos($link, $domain) !== false) {
                        $type = $domainType;
                        break;
                    }
                }

                // Only create the social link if it doesn't already exist for this tool
                ToolSocialLink::firstOrCreate(
                    ['tool_id' => $tool->id, 'link' => $link],
                    ['type' => $type]
                );
            }
        }

        if (!empty($tags)) {
            foreach ($tags as $tagName) {
                // Remove the "#" from the beginning of the tag name and create a slug.
                $tagNameCleaned = ltrim($tagName, '#');
                $tagSlug = Str::slug($tagNameCleaned);

                // Find or create the tag
                $tag = Tag::firstOrCreate(
                    ['slug' => $tagSlug],
                    ['name' => $tagNameCleaned, 'type' => $tagType]
                );

                // Attach the tag to the tool without detaching existing ones. 
                // This will insert a record in the pivot table (tag_tool) only if it doesn't exist.
                $tool->tags()->syncWithoutDetaching([$tag->id]);
            }
        }
    }

    echo 'success ...';
});

require __DIR__ . '/auth.php';
