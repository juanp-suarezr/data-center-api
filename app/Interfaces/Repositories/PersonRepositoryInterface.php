<?php

declare(strict_types=1);

namespace App\Interfaces\Repositories;

use App\Models\Persona;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PersonRepositoryInterface
{
    public function findByUuid(string $uuid): ?Person;

    public function findByDocument(string $tipoDocumento, string $numeroDocumento): ?Person;

    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Person;

    public function update(Person $person, array $data): Person;

    public function existsByDocument(string $tipoDocumento, string $numeroDocumento): bool;

    public function getProjectRelations(Person $person): Collection;

    public function attachProjectRelation(Person $person, string $clientId, array $relationData): void;

    public function recordAudit(Person $person, string $action, ?array $old = null, ?array $new = null, ?string $clientId = null, ?string $requestId = null): void;
}
