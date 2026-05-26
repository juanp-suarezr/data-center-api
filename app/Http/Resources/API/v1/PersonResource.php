<?php

declare(strict_types=1);

namespace App\Http\Resources\API\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'full_name' => $this->full_name,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
            'genero' => $this->genero,
            'data_quality_score' => $this->data_quality_score,
            'last_verified_at' => $this->last_verified_at?->toISOString(),
            'source_project' => $this->source_project,
            'contacts' => PersonContactResource::collection($this->whenLoaded('contacts')),
            'addresses' => PersonAddressResource::collection($this->whenLoaded('addresses')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
