<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['portfolio_id', 'reference_no', 'proof_path', 'amount', 'status'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function portfolio(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
