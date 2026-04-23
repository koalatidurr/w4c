<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SortItem extends Model
{
    use HasFactory;

    protected $fillable = ['sort_id', 'waste_id', 'weight'];
    protected $casts = ['weight' => 'decimal:2'];

    public function sort(): BelongsTo
    {
        return $this->belongsTo(Sort::class);
    }

    public function waste(): BelongsTo
    {
        return $this->belongsTo(Waste::class);
    }
}
