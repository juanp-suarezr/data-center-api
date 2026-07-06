<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\DocumentType;
use Illuminate\Support\Arr;

readonly class BulkPersonUploadDTO
{
    public function __construct(
        public array $rows,
        public string $sourceProject,
        public string $clientId,
        public bool $skipInvalid = true,
        public bool $updateExisting = true,
    ) {}

    public static function fromUploadedFile(string $content, array $options = []): self
    {
        $rows = self::parseCsv($content);

        return new self(
            rows: $rows,
            sourceProject: Arr::get($options, 'source_project', config('app.name')),
            clientId: Arr::get($options, 'client_id'),
            skipInvalid: Arr::get($options, 'skip_invalid', true),
            updateExisting: Arr::get($options, 'update_existing', true),
        );
    }

    public static function parseCsv(string $content): array
    {
        $lines = str_getcsv($content, "\n");
        if (empty($lines)) {
            return [];
        }

        $headers = null;
        $rows = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $row = str_getcsv($line);

            if ($headers === null) {
                $headers = array_map('trim', array_map('mb_strtolower', $row));
                continue;
            }

            $rows[] = array_combine($headers, $row);
        }

        return $rows;
    }

    public function getValidRows(): array
    {
        return array_filter($this->rows, function ($row) {
            $tipoDoc = strtoupper($row['tipo_documento'] ?? '');
            $numDoc = $row['numero_documento'] ?? '';

            $hasValidDocument = in_array($tipoDoc, DocumentType::values(), true) && !empty($numDoc) && strlen($numDoc) >= 4;

            return $hasValidDocument;
        });
    }

    public function getInvalidRows(): array
    {
        return array_filter($this->rows, function ($row) {
            $tipoDoc = strtoupper($row['tipo_documento'] ?? '');
            $numDoc = $row['numero_documento'] ?? '';

            $isValid = in_array($tipoDoc, DocumentType::values(), true) && !empty($numDoc) && strlen($numDoc) >= 4;

            return !$isValid;
        });
    }
}