<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
#[OA\Post(
        path: "/api/v1/auth/token",
        summary: "Obtener token de acceso para un proyecto cliente",
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Token generado exitosamente",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(property: 'token_type', type: 'string'),
                        new OA\Property(property: 'client', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'string'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'slug', type: 'string'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Credenciales inválidas"),
        ]
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "application/json",
            schema: new OA\Schema(
                type: "object",
                properties: [
                    new OA\Property(property: "slug", type: "string"),
                    new OA\Property(property: "secret", type: "string"),
                ],
                required: ["slug", "secret"]
            )
        )
    )]
    public function issueToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|exists:api_clients,slug',
            'secret' => 'required|string', // In real prod this would be hashed secret or JWT
        ]);

        $client = ApiClient::where('slug', $validated['slug'])
            ->where('is_active', true)
            ->first();

        if (! $client) {
            return response()->json(['success' => false, 'message' => 'Cliente no autorizado'], 401);
        }

        // For demo: we accept any "secret" matching name or a simple check.
        // In production: use proper client secrets stored hashed.
        
        if ($validated['secret'] !== config('app.client_secret_salt').$client->slug) {
            return response()->json(['success' => false, 'message' => 'Credenciales inválidas'], 401);
        }

        $token = $client->issueToken('api-access', ['person:read', 'person:write', 'person:sync']);

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'slug' => $client->slug,
            ],
        ]);
    }
}
