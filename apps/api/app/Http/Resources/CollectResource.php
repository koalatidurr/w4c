<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->schedule_id,
            'code' => $this->code,
            'status' => $this->status,
            'collect_items' => CollectItemResource::collection($this->whenLoaded('collectItems')),
            'sort' => new SortResource($this->whenLoaded('sort')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
