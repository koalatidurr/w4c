<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trashbag_id' => $this->trashbag_id,
            'trashbag_name' => $this->whenLoaded('trashbag', fn() => $this->trashbag->name),
            'quantity' => $this->quantity,
        ];
    }
}
