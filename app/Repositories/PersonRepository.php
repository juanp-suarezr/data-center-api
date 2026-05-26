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

class PersonRepository implements PersonRepositoryInterface
{
    public function findByUuid(string $uuid): ?Person
    {
        return Person::with(['contacts', 'addresses', 'projectRelations.apiClient'])
            ->find($uuid);
    }

    public function findByDocument(string $tipoDocumento, string $numeroDocumento): ?Person
    {
        return Person::with(['contacts', 'addresses'])
            ->byDocument($tipoDocumento, $numeroDocumento)
            ->first();
    }

    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = Person::query()->with(['primaryContact', 'primaryAddress']);

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

    public function create(array $data): Person
    {
        return DB::transaction(function () use ($data) {
            $person = Person::create($data);

            // Auto-create project relation if client provided
            if (! empty($data['created_by_client_id'])) {
                $this->attachProjectRelation($person, $data['created_by_client_id'], [
                    'trust_level' => 'medium',
                    'data_quality_score' => $person->data_quality_score,
                    'last_action' => 'create',
                ]);
            }

            return $person;
        });
    }

    public function update(Person $person, array $data): Person
    {
        $old = $person->toArray();

        $person->update($data);

        $person->recalculateQualityScore();

        return $person->fresh();
    }

    public function existsByDocument(string $tipoDocumento, string $numeroDocumento): bool
    {
        return Person::byDocument($tipoDocumento, $numeroDocumento)->exists();
    }

    public function getProjectRelations(Person $person): Collection
    {
        return $person->projectRelations()->with('apiClient')->get();
    }

    public function attachProjectRelation(Person $person, string $clientId, array $relationData): void
    {
        PersonProjectRelation::updateOrCreate(
            [
                'person_id' => $person->id,
                'api_client_id' => $clientId,
            ],
            array_merge($relationData, [
                'last_synced_at' => now(),
            ])
        );
    }

    public function recordAudit(Person $person, string $action, ?array $old = null, ?array $new = null, ?string $clientId = null, ?string $requestId = null): void
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
