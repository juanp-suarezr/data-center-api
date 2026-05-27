<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\Persona;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PersonRepositoryInterface
{
    public function findByUuid(string $uuid): ?Persona;

    public function findByDocument(string $tipoDocumento, string $numeroDocumento): ?Persona;

    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Persona;

    public function update(Persona $person, array $data): Persona;

    public function existsByDocument(string $tipoDocumento, string $numeroDocumento): bool;

    public function getProjectRelations(Persona $person): Collection;

    public function attachProjectRelation(Persona $person, string $apiClientSlug, array $relationData): void;

    public function recordAudit(Persona $person, string $action, ?array $old = null, ?array $new = null, ?string $clientId = null, ?string $requestId = null): void;
}
