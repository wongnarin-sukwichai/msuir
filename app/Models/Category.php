<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name_en', 'slug', 'sort_order'];

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class)->orderBy('sort_order');
    }
}
