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
        public string $nombres,
        public string $apellidos,
        public ?string $fechaNacimiento = null,
        public ?string $genero = null,
        public ?string $correo = null,
        public ?string $telefono = null,
        public ?array $direccion = null,
        public ?string $sourceProject = null,
        public ?string $clientId = null,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tipoDocumento: strtoupper(Arr::get($data, 'tipo_documento', '')),
            numeroDocumento: Arr::get($data, 'numero_documento', ''),
            nombres: trim(Arr::get($data, 'nombres', '')),
            apellidos: trim(Arr::get($data, 'apellidos', '')),
            fechaNacimiento: Arr::get($data, 'fecha_nacimiento'),
            genero: Arr::get($data, 'genero'),
            correo: Arr::get($data, 'correo'),
            telefono: Arr::get($data, 'telefono'),
            direccion: Arr::get($data, 'direccion'),
            sourceProject: Arr::get($data, 'source_project'),
            clientId: Arr::get($data, 'client_id'),
            metadata: Arr::get($data, 'metadata', []),
        );
    }

    public function toArray(): array
    {
        return [
            'tipo_documento' => $this->tipoDocumento,
            'numero_documento' => $this->numeroDocumento,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'fecha_nacimiento' => $this->fechaNacimiento,
            'genero' => $this->genero,
            'source_project' => $this->sourceProject,
            'created_by_client_id' => $this->clientId,
            'metadata' => $this->metadata,
        ];
    }

    public function isValidDocument(): bool
    {
        return in_array($this->tipoDocumento, DocumentType::values(), true) && ! empty($this->numeroDocumento);
    }
}
