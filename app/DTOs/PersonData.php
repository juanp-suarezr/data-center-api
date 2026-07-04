<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\DocumentType;
use Illuminate\Support\Arr;

readonly class PersonData
{
    public function __construct(
        public string $tipoDocumento,
        public string $numeroDocumento,
        public string $nombreCompleto,
        public string $nombres,
        public string $apellidos,
        public ?int $edad = null,
        public ?string $fechaNacimiento = null,
        public ?string $genero = null,
        public ?string $correo = null,
        public ?string $telefono = null,
        public string|array|null $direccion = null,
        public ?string $sector = null,
        public ?string $comuna = null,
        public ?string $barrio = null,
        public ?string $condicion = null,
        public ?string $etnia = null,
        public ?string $nivelEstudio = null,
        public bool $dignatario = false,
        public ?string $sourceProject = null,
        public ?string $clientId = null,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $nombres = trim(Arr::get($data, 'nombres', ''));
        $apellidos = trim(Arr::get($data, 'apellidos', ''));
        $nombreCompleto = trim($nombres.' '.$apellidos);

        return new self(
            tipoDocumento: strtoupper(Arr::get($data, 'tipo_documento', '')),
            numeroDocumento: (string) Arr::get($data, 'numero_documento', ''),
            nombres: $nombres,
            apellidos: $apellidos,
            nombreCompleto: $nombreCompleto,
            edad: Arr::get($data, 'edad') !== null && Arr::get($data, 'edad') !== '' ? (int) Arr::get($data, 'edad') : null,
            fechaNacimiento: Arr::get($data, 'fecha_nacimiento'),
            genero: Arr::get($data, 'genero'),
            correo: Arr::get($data, 'correo'),
            telefono: Arr::get($data, 'telefono'),
            direccion: Arr::get($data, 'direccion'),
            sector: Arr::get($data, 'sector'),
            comuna: Arr::get($data, 'comuna'),
            barrio: Arr::get($data, 'barrio'),
            condicion: Arr::get($data, 'condicion'),
            etnia: Arr::get($data, 'etnia'),
            nivelEstudio: Arr::get($data, 'nivel_estudio'),
            dignatario: (bool) Arr::get($data, 'dignatario', false),
            sourceProject: Arr::get($data, 'source_project'),
            clientId: Arr::get($data, 'client_id'),
            metadata: Arr::get($data, 'metadata', []),
        );
    }

    public function toArray(): array
    {
        return [
            'nombre_completo' => $this->nombreCompleto,
            'tipo_documento' => $this->tipoDocumento,
            'numero_documento' => $this->numeroDocumento,
            'edad' => $this->edad,
            'nacimiento' => $this->fechaNacimiento,
            'genero' => $this->genero,
            'telefono' => $this->telefono,
            'email' => $this->correo,
            'direccion' => is_array($this->direccion)
                ? trim(($this->direccion['via_principal'] ?? '').' '.($this->direccion['complemento'] ?? ''))
                : $this->direccion,
            'sector' => $this->sector,
            'barrio' => $this->barrio,
            'comuna' => $this->comuna,
            'source_project' => $this->sourceProject,
            'created_by_client_id' => $this->clientId,
            'data_quality_score' => 50,
            'metadata' => $this->metadata,
        ];
    }

    public function isValidDocument(): bool
    {
        return in_array($this->tipoDocumento, DocumentType::values(), true) && ! empty($this->numeroDocumento);
    }
}
