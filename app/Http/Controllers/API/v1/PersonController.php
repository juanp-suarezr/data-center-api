<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\v1;

use App\DTOs\PersonData;
use App\Models\Persona;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\PersonSearchRequest;
use App\Http\Requests\API\v1\PersonSyncRequest;
use App\Http\Resources\API\v1\PersonResource;
use App\Services\PersonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Persons', description: 'Master Data Management - Personas')]
class PersonController extends Controller
{
    public function __construct(
        private readonly PersonService $personService
    ) {}

#[OA\Get(
        path: "/api/v1/persons/search",
        summary: "Buscar persona por documento o datos básicos",
        tags: ["Persons"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Resultados de búsqueda"),
        ]
    )]
    public function search(PersonSearchRequest $request): JsonResponse
    {
        

        $persons = $this->personService->searchPerson($request->validated());

        return response()->json([
            'success' => true,
            'data' => PersonResource::collection($persons),
            'meta' => [
                'total' => $persons->total(),
                'current_page' => $persons->currentPage(),
            ],
        ]);
    }

#[OA\Post(
        path: "/api/v1/persons/sync",
        summary: "Crear o actualizar persona (idempotente)",
        tags: ["Persons"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 201, description: "Persona creada"),
            new OA\Response(response: 200, description: "Persona actualizada"),
            new OA\Response(response: 422, description: "Validación fallida"),
        ]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "application/json",
            schema: new OA\Schema(ref: "#/components/schemas/PersonSyncRequest")
        )
    )]
    public function sync(PersonSyncRequest $request): JsonResponse
    {

        $client = $request->user(); // ApiClient via Sanctum

        $dto = PersonData::fromArray($request->validated() + ['client_id' => $client->id]);

        $result = $this->personService->syncPerson($dto, $client);

        $status = $result['action'] === 'created' ? 201 : 200;

        return response()->json([
            'success' => true,
            'action' => $result['action'],
            'message' => $result['message'],
            'data' => new PersonResource($result['person']),
        ], $status);
    }

    #[OA\Get(
        path: "/api/v1/persons/{uuid}",
        summary: "Obtener persona por UUID",
        tags: ["Persons"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string'), description: 'UUID de la persona'),
        ],
        responses: [
            new OA\Response(response: 200, description: "Persona encontrada"),
            new OA\Response(response: 404, description: "No encontrada"),
        ]
    )]
    public function show(string $uuid): JsonResponse
    {
        $person = $this->personService->getPerson($uuid);

        if (!$person) {
            return response()->json([
                'success' => false,
                'message' => 'Persona no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PersonResource($person->load(['contacts', 'addresses'])),
        ]);
    }

    #[OA\Get(
        path: "/api/v1/persons/find",
        summary: "Buscar persona por tipo y número de documento",
        tags: ["Persons"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: 'tipo_documento', in: 'query', required: true, schema: new OA\Schema(type: 'string'), description: 'Tipo de documento (CC, TI, CE, etc.)'),
            new OA\Parameter(name: 'numero_documento', in: 'query', required: true, schema: new OA\Schema(type: 'string'), description: 'Número del documento'),
            new OA\Parameter(name: 'source_project', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Fuente del proyecto, para excluir personas sincronizadas con este proyecto'),
        ],
        responses: [
            new OA\Response(response: 200, description: "Persona encontrada"),
            new OA\Response(response: 404, description: "No encontrada"),
        ]
    )]
    public function findByDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tipo_documento' => ['required', 'string'],
            'numero_documento' => ['required', 'string'],
            'source_project' => ['nullable', 'string', 'max:100'],
        ]);

        $person = $this->personService->findByDocument($validated['tipo_documento'], $validated['numero_documento'], $validated['source_project'] ?? null);

        if (! $person) {
            return response()->json(['success' => false, 'message' => 'Persona no encontrada'], 404);
        }

        return response()->json(['success' => true, 'data' => new PersonResource($person->load(['contacts','addresses']))]);
}
}
