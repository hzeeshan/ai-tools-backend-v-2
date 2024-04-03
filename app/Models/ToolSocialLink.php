<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolSocialLink extends Model
{
    use HasFactory;

    protected $table = 'tools_social_links';
    protected $guarded = [];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }
}
