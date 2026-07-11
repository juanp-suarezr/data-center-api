<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Interfaces\Repositories\PersonRepositoryInterface;
use App\Models\ApiClient;
use App\Models\AuditLog;
use App\Models\Persona;
use App\Models\PersonProjectRelation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonRepository implements PersonRepositoryInterface
{
    public function findByUuid(string $uuid): ?Persona
    {
        return Persona::with(['contacts', 'addresses', 'projectRelations.apiClient'])
            ->find($uuid);
    }

    public function findByDocument(string $tipoDocumento, string $numeroDocumento, ?string $sourceProject = null): ?Persona
    {
        $query = Persona::with(['contacts', 'addresses'])
            ->byDocument($tipoDocumento, $numeroDocumento);

        // Solo excluir si sourceProject viene explícito (endpoint find/sync).
        // source_project es un array (JSON), por lo que se excluyen las personas
        // cuyo array de proyectos CONTENGA el proyecto indicado. Así, tanto una
        // persona registrada solo en "votaciones" como otra en "votaciones" y
        // "vive-digital" quedan excluidas al filtrar por "votaciones".
        if ($sourceProject) {
            $query->whereJsonDoesntContain('source_project', $sourceProject);
        }

        return $query->first();
    }

    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = Persona::query()->with(['primaryContact', 'primaryAddress']);

        if (! empty($criteria['document'])) {
            $query->where('numero_documento', 'like', '%'.$criteria['document'].'%');
        }

        if (! empty($criteria['nombres'])) {
            $query->where('nombres', 'like', '%'.$criteria['nombres'].'%');
        }

        if (! empty($criteria['apellidos'])) {
            $query->where('apellidos', 'like', '%'.$criteria['apellidos'].'%');
        }

        if (! empty($criteria['municipio'])) {
            $query->whereHas('addresses', fn ($q) => $q->where('municipio', $criteria['municipio']));
        }

        return $query->orderByDesc('data_quality_score')
            ->paginate($perPage);
    }

    public function create(array $data): Persona
    {
        return DB::transaction(function () use ($data) {
            $person = Persona::create($data);

            return $person;
        });
    }

    public function update(Persona $person, array $data): Persona
    {
        $old = $person->toArray();

        $person->update($data);

        $person->recalculateQualityScore();

        return $person->fresh();
    }

    public function existsByDocument(string $tipoDocumento, string $numeroDocumento): bool
    {
        return Persona::byDocument($tipoDocumento, $numeroDocumento)->exists();
    }

    public function getProjectRelations(Persona $person): Collection
    {
        return $person->projectRelations()->with('apiClient')->get();
    }

    public function attachProjectRelation(Persona $person, string $apiClientSlug, array $relationData): void
    {
        $apiClient = ApiClient::where('slug', $apiClientSlug)->firstOrFail();

        PersonProjectRelation::updateOrCreate(
            [
                'person_id' => $person->id,
                'api_client_id' => $apiClient->id,
            ],
            array_merge($relationData, [
                'last_synced_at' => now(),
            ])
        );
    }

    public function recordAudit(Persona $person, string $action, ?array $old = null, ?array $new = null, ?string $clientId = null, ?string $requestId = null): void
    {
        AuditLog::record(
            $person,
            $action,
            $clientId ? ApiClient::find($clientId) : null,
            $old,
            $new,
            $requestId,
            request()?->ip(),
            request()?->path()
        );
    }
}
