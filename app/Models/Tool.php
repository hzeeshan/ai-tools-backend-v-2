<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasFactory;

    protected $table = 'tools';
    protected $guarded = [];

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tag_tool');
    }

    public function socialLinks()
    {
        return $this->hasMany(ToolSocialLink::class);
    }

    public function ratings()
    {
        return $this->hasMany(ToolRating::class);
    }
}
