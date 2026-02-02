<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'user_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    /**
     * Get the user that created the inventory.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the inventory.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }
}
