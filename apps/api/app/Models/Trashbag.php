<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trashbag extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function collectItems(): HasMany
    {
        return $this->hasMany(CollectItem::class);
    }
}
