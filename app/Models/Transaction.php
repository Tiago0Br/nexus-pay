<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transaction extends Model
{
    protected $fillable = [
        'client_id',
        'gateway_id',
        'external_id',
        'status',
        'amount',
        'card_last_numbers',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'transaction_products')
            ->withPivot('quantity');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
