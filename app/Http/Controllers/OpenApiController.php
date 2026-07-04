<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * OpenAPI root definition used by L5-Swagger to generate the docs.
 * This file provides the required @OA\Info annotation.
 */
#[OA\Info(
    title: "Central Data API",
    version: "1.0.0",
    description: "Centralized Master Data API - Personas (MDM)",
    contact: new OA\Contact(name: "API Support", email: "support@example.com")
)]
#[OA\Server(url: "http://localhost:8001", description: "Local server")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Provide the access token as: Bearer {token}"
)]
class OpenApiController
{
    // This class is only used for annotations/attributes and is not instantiated.
}
