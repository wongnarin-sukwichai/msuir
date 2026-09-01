<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPerson extends Model
{
    protected $table = 'item_person';

    protected $fillable = ['item_id', 'name', 'role', 'sort_order'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
