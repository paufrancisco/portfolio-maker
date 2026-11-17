<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    public const TEMPLATES = [
        'classic' => 'Classic',
        'modern' => 'Modern',
        'minimal' => 'Minimal',
    ];

    protected $fillable = ['user_id', 'title', 'template_key', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public static function blankData(): array
    {
        return [
            'personal' => ['name' => '', 'headline' => '', 'email' => '', 'phone' => '', 'location' => '', 'website' => '', 'linkedin' => ''],
            'summary' => '',
            'experience' => [],
            'education' => [],
            'skills' => [],
            'projects' => [],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
