<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SortItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'waste_id' => $this->waste_id,
            'waste_name' => $this->whenLoaded('waste', fn() => $this->waste->name),
            'weight' => (float) $this->weight,
        ];
    }
}
