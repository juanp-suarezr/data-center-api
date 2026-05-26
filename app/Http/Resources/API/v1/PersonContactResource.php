<?php

declare(strict_types=1);

namespace App\Http\Resources\API\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'value' => $this->value,
            'is_primary' => $this->is_primary,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at?->toISOString(),
        ];
    }
}
