<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectItem extends Model
{
    use HasFactory;

    protected $fillable = ['collect_id', 'trashbag_id', 'quantity'];

    public function collect(): BelongsTo
    {
        return $this->belongsTo(Collect::class);
    }

    public function trashbag(): BelongsTo
    {
        return $this->belongsTo(Trashbag::class);
    }
}
