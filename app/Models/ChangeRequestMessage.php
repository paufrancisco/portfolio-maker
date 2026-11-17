<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeRequestMessage extends Model
{
    protected $fillable = [
        'change_request_id',
        'user_id',
        'is_admin',
        'body',
        'image_urls',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'image_urls' => 'array',
    ];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
