<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChangeRequest extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'quoted' => 'Quoted',
        'in_progress' => 'Currently working on',
        'fixed' => 'Fixed',
        'not_fixed' => 'Not fixed yet',
    ];

    public const TYPES = [
        'minor_fix' => 'Minor fix',
        'additional' => 'Additional work',
    ];

    protected $fillable = [
        'portfolio_id',
        'user_id',
        'title',
        'type',
        'status',
        'quote_amount',
        'quote_note',
        'payment_status',
        'payment_reference',
        'payment_proof_path',
        'paid_at',
        'last_message_at',
        'admin_read_at',
        'customer_read_at',
    ];

    protected $casts = [
        'quote_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'last_message_at' => 'datetime',
        'admin_read_at' => 'datetime',
        'customer_read_at' => 'datetime',
    ];

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChangeRequestMessage::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function typeLabel(): ?string
    {
        return $this->type ? (self::TYPES[$this->type] ?? $this->type) : null;
    }
}
