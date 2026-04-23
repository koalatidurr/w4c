<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = ['client_id', 'date'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function collect(): HasOne
    {
        return $this->hasOne(Collect::class);
    }
}
