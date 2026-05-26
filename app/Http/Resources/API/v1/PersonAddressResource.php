<?php

declare(strict_types=1);

namespace App\Http\Resources\API\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'is_primary' => $this->is_primary,
            'full_address' => $this->full_address,
            'barrio' => $this->barrio,
            'comuna' => $this->comuna,
            'municipio' => $this->municipio,
            'departamento' => $this->departamento,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
        ];
    }
}
