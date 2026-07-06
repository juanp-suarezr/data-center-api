<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\BulkPersonUploadRequest;
use App\Services\BulkPersonService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Bulk Upload', description: 'Massive data loading operations')]
class BulkUploadController extends Controller
{
    public function __construct(
        private readonly BulkPersonService $bulkService
    ) {}

    #[OA\Post(
        path: "/api/v1/bulk/persons",
        summary: "Cargar personas masivamente desde CSV",
        description: "Inicia un proceso en cola para cargar personas desde un archivo CSV. El archivo debe tener encabezados con los campos requeridos.",
        tags: ["Bulk Upload"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 202, description: "Procesamiento en cola iniciado"),
            new OA\Response(response: 422, description: "Validación fallida"),
        ]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                properties: [
                    new OA\Property(property: "file", type: "string", format: "binary", description: "Archivo CSV con datos de personas"),
                    new OA\Property(property: "skip_invalid", type: "boolean", description: "Omitir registros inválidos"),
                    new OA\Property(property: "update_existing", type: "boolean", description: "Actualizar registros existentes"),
                    new OA\Property(property: "source_project", type: "string", description: "Proyecto origen"),
                ],
                required: ["file"]
            )
        )
    )]
    public function uploadPersons(BulkPersonUploadRequest $request): JsonResponse
    {
        $client = $request->user();
        $options = $request->validated();

        $content = file_get_contents($request->file('file')->getRealPath());

        $result = $this->bulkService->processUpload($content, $client, [
            'skip_invalid' => $options['skip_invalid'] ?? true,
            'update_existing' => $options['update_existing'] ?? true,
            'source_project' => $options['source_project'] ?? $client->slug,
        ]);

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Archivo recibido y procesamiento en cola iniciado',
        ], 202);
    }

    #[OA\Get(
        path: "/api/v1/bulk/persons/{batchId}",
        summary: "Obtener estado del procesamiento masivo",
        description: "Consulta el progreso del procesamiento de carga masiva",
        tags: ["Bulk Upload"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "batchId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", format: "uuid"),
                description: "ID del batch de procesamiento"
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "Estado del batch"),
            new OA\Response(response: 404, description: "Batch no encontrado"),
        ]
    )]
    public function getBatchStatus(string $batchId): JsonResponse
    {
        $status = $this->bulkService->getBatchStatus($batchId);

        if ($status['status'] === 'not_found') {
            return response()->json([
                'success' => false,
                'message' => 'Batch no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }
}