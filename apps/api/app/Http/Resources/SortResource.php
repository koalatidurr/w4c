<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SortResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'collect_id' => $this->collect_id,
            'code' => $this->code,
            'sort_items' => SortItemResource::collection($this->whenLoaded('sortItems')),
            'total_weight' => $this->whenLoaded('sortItems', fn() =>
                $this->sortItems->sum('weight')
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
