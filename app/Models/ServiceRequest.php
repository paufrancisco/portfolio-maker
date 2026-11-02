<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    public const TYPES = [
        'thesis_build' => 'Thesis: build a system',
        'thesis_debug' => 'Thesis: debug my project',
        'math_tutor' => 'Math tutor (recorded walkthrough)',
        'programming_tutor' => 'Programming tutor (recorded walkthrough)',
    ];

    public const STATUSES = [
        'received' => 'Received',
        'quoted' => 'Quoted',
        'payment_submitted' => 'Payment submitted',
        'paid' => 'Paid',
        'delivered' => 'Delivered',
        'rejected' => 'Rejected',
    ];

    protected $fillable = [
        'type', 'email', 'details', 'attachment_path', 'token', 'status',
        'quote_amount', 'quote_note', 'delivery_url', 'admin_note', 'paid_at',
        'payment_reference', 'payment_proof_path', 'payment_submitted_at',
    ];

    protected $casts = [
        'quote_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'payment_submitted_at' => 'datetime',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
