<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = ['key', 'name', 'theme_options', 'is_active'];

    protected $casts = [
        'theme_options' => 'array',
        'is_active' => 'boolean',
    ];
}
