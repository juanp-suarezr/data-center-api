<?php

declare(strict_types=1);

namespace App\Http\Resources\API\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    private function splitNombreCompleto(): array
    {
        $parts = explode(' ', trim((string) $this->nombre_completo));
        if (count($parts) === 0) {
            return ['nombres' => '', 'apellidos' => ''];
        }
        if (count($parts) === 1) {
            return ['nombres' => $parts[0], 'apellidos' => ''];
        }
        return [
            'nombres' => $parts[0],
            'apellidos' => implode(' ', array_slice($parts, 1)),
        ];
    }

    public function toArray(Request $request): array
    {
        $nameParts = $this->splitNombreCompleto();

        return [
            'id' => $this->id,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'nombre_completo' => $this->nombre_completo,
            'nombres' => $nameParts['nombres'],
            'apellidos' => $nameParts['apellidos'],
            'full_name' => $this->nombre_completo,
            'fecha_nacimiento' => $this->nacimiento?->format('Y-m-d'),
            'nacimiento' => $this->nacimiento?->format('Y-m-d'),
            'edad' => (int) $this->edad,
            'genero' => $this->genero,
            'identificacion' => $this->tipo_documento.'-'.$this->numero_documento,
            'direccion' => $this->direccion,
            'sector' => $this->sector,
            'barrio' => $this->barrio,
            'comuna' => $this->comuna,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'condicion' => $this->condicion,
            'etnia' => $this->etnia,
            'nivel_estudio' => $this->nivel_estudio,
            'dignatario' => (bool) $this->dignatario,
            'data_quality_score' => (int) $this->data_quality_score,
            'last_verified_at' => $this->last_verified_at?->toISOString(),
            'source_project' => $this->source_project,
            'contacts' => PersonContactResource::collection($this->whenLoaded('contacts')),
            'addresses' => PersonAddressResource::collection($this->whenLoaded('addresses')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
