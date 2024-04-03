<?php

namespace App\Services;

use App\Models\Tag;

class TagService
{
    public function index()
    {
        return Tag::all();
    }
}
