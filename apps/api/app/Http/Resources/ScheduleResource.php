<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', fn() => $this->client->name),
            'date' => $this->date,
            'has_collect' => $this->collect !== null,
            'has_sort' => $this->collect?->sort !== null,
            'collect' => new CollectResource($this->whenLoaded('collect')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
