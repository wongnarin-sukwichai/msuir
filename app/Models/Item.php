<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'collection_id',
        'department_id',
        'owner_id',
        'title',
        'description',
        'year_issued',
        'language',
        'rights',
        'format',
        'degree',
        'fulltext_url',
        'fulltext_path',
        'status',
        'review_note',
    ];

    protected $casts = [
        'year_issued' => 'integer',
    ];

    /** Resolved link to the full text: a pasted URL wins, else the uploaded PDF on the public disk. */
    protected function fulltext(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->fulltext_url
            ?: ($this->fulltext_path ? Storage::disk('public')->url($this->fulltext_path) : null));
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Dept::class, 'department_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function titles(): HasMany
    {
        return $this->hasMany(ItemTitle::class)->orderBy('sort_order');
    }

    /** ผู้แต่งทั้งหมด (creator + contributor) เรียงตามลำดับต้นฉบับ */
    public function people(): HasMany
    {
        return $this->hasMany(ItemPerson::class)->orderBy('sort_order');
    }

    public function creators(): HasMany
    {
        return $this->people()->where('role', 'creator');
    }

    public function contributors(): HasMany
    {
        return $this->people()->where('role', 'contributor');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(ItemSubject::class)->orderBy('sort_order');
    }
}
