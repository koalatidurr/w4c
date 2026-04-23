<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Collect extends Model
{
    use HasFactory;

    protected $fillable = ['schedule_id', 'code', 'status'];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function collectItems(): HasMany
    {
        return $this->hasMany(CollectItem::class);
    }

    public function sort(): HasOne
    {
        return $this->hasOne(Sort::class);
    }
}
