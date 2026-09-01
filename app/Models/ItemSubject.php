<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemSubject extends Model
{
    protected $table = 'item_subject';

    protected $fillable = ['item_id', 'value', 'sort_order'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
