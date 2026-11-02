<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    protected $fillable = [
        'user_id', 'template_key', 'theme', 'slug', 'data', 'published_data', 'published_at', 'update_requested_at', 'resume_path', 'status',
    ];

    protected $casts = [
        'data' => 'array',
        'published_data' => 'array',
        'published_at' => 'datetime',
        'update_requested_at' => 'datetime',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
