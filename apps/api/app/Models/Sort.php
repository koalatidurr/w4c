<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sort extends Model
{
    use HasFactory;

    protected $fillable = ['collect_id', 'code'];

    public function collect(): BelongsTo
    {
        return $this->belongsTo(Collect::class);
    }

    public function sortItems(): HasMany
    {
        return $this->hasMany(SortItem::class);
    }
}
