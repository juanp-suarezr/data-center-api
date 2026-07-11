<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'PersonSyncRequest', type: 'object')]
class PersonSyncRequestSchema
{
    #[OA\Property(property: 'tipo_documento', type: 'string')]
    public string $tipo_documento;

    #[OA\Property(property: 'numero_documento', type: 'string')]
    public string $numero_documento;

    #[OA\Property(property: 'nombres', type: 'string')]
    public string $nombres;

    #[OA\Property(property: 'apellidos', type: 'string')]
    public string $apellidos;

    #[OA\Property(property: 'fecha_nacimiento', type: 'string', format: 'date')]
    public ?string $fecha_nacimiento = null;

    #[OA\Property(property: 'genero', type: 'string')]
    public ?string $genero = null;

    #[OA\Property(property: 'correo', type: 'string')]
    public ?string $correo = null;

    #[OA\Property(property: 'telefono', type: 'string')]
    public ?string $telefono = null;

    #[OA\Property(property: 'direccion', type: 'object')]
    public ?array $direccion = null;

    #[OA\Property(
        property: 'source_project',
        type: 'array',
        items: new OA\Items(type: 'string', maxLength: 100),
        description: 'Proyectos origen (ej: ["vive-digital", "votaciones"]). Se acumulan al sincronizar.'
    )]
    public ?array $source_project = null;
}
