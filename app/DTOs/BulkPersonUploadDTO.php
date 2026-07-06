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

        $delimiter = self::detectDelimiter($content);

        $headers = null;
        $rows = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $row = str_getcsv($line, $delimiter);

            if ($headers === null) {
                $headers = array_values(array_filter(array_map('trim', array_map('mb_strtolower', $row)), fn($h) => $h !== ''));

                if (empty($headers)) {
                    continue;
                }

                continue;
            }

            $row = array_slice($row, 0, count($headers));
            $row = array_pad($row, count($headers), '');

            if (count($headers) !== count($row)) {
                continue;
            }

            $rows[] = array_combine($headers, $row);
        }

        return $rows;
    }

    public static function detectDelimiter(string $content): string
    {
        $firstLine = strtok($content, "\n") ?: $content;
        $counts = [
            ';' => substr_count($firstLine, ';'),
            ',' => substr_count($firstLine, ','),
            "\t" => substr_count($firstLine, "\t"),
        ];

        arsort($counts);

        return array_key_first($counts) ?? ',';
    }

    public function getValidRows(): array
    {
        return array_filter($this->rows, function ($row) {
            $cleaned = $this->cleanRow($row);

            $tipoDoc = $cleaned['tipo_documento'] ?? '';
            $numDoc = $cleaned['numero_documento'] ?? '';

            return ! empty($tipoDoc) && ! empty($numDoc) && strlen($numDoc) >= 4;
        });
    }

    public function getInvalidRows(): array
    {
        return array_filter($this->rows, function ($row) {
            $cleaned = $this->cleanRow($row);

            $tipoDoc = $cleaned['tipo_documento'] ?? '';
            $numDoc = $cleaned['numero_documento'] ?? '';

            return empty($tipoDoc) || empty($numDoc) || strlen($numDoc) < 4;
        });
    }

    public function getTipoDocumentoDataCenter(string $tipo): ?string
    {
        $map = [
            'Tarjeta de Identidad' => 'TI',
            'Registro Civil' => 'RC',
            'Cédula Ciudadanía' => 'CC',
            'Cédula Extranjería' => 'CE',
            'Pasaporte' => 'PP',
            'Permiso por Protección Temporal' => 'PPT',
            'Permiso Especial PEP' => 'PEP',
            'NIT' => 'NIT',
            'Otro' => 'O',
        ];

        return $map[$tipo] ?? $tipo;
    }

    public function getGeneroDataCenter(string $genero): ?string
    {
        $map = [
            'Masculino' => 'M',
            'Femenino' => 'F',
            'Otro' => 'O',
        ];

        return $map[$genero] ?? $genero;
    }

    public function getDateDataCenter(?int $edad): ?string
    {
        if ($edad === null || $edad <= 0) {
            return null;
        }

        return date('Y-m-d', strtotime('-'.$edad.' years'));
    }

    public function getNombreEtniaDataCenter(string $code): ?string
    {
        $map = [
            'NA' => 'No aplica',
            'mestizo' => 'Mestizo',
            'afro' => 'Afrodescendiente',
            'indigena' => 'Indígena',
            'palanquero' => 'Palanquero',
            'rom' => 'ROM',
        ];

        return $map[$code] ?? $code;
    }

    public function getNombreNivelEstudioDataCenter(string $code): ?string
    {
        $map = [
            'NA' => 'Ninguno',
            'primaria' => 'Primaria',
            'secundaria' => 'Secundaria',
            'tecnico' => 'Tecnico',
            'tecnologico' => 'Tecnologico',
            'universitario' => 'Universitario',
            'postgrado' => 'Postgrado',
        ];

        return $map[$code] ?? $code;
    }

    public function getNombreCondicionDataCenter(string $code): ?string
    {
        $map = [
            'NA' => 'Sin condición',
            'discapacitado' => 'Persona con discapacidad',
            'desplazados' => 'Desplazados',
            'victimasConfArm' => 'Victimas',
            'mujerCabHogar' => 'Mujer cabeza de hogar',
            'hombreCabHogar' => 'Padre cabeza de hogar',
            'habitanteCalle' => 'Habitante de calle',
            'migrante' => 'Migrante',
        ];

        return $map[$code] ?? $code;
    }

    public function validateRow(array $row): bool
    {
        $tipoDoc = $this->getTipoDocumentoDataCenter($row['tipo_documento'] ?? '');
        $numDoc = $row['numero_documento'] ?? '';

        return in_array($tipoDoc, DocumentType::values(), true) && ! empty($numDoc) && strlen($numDoc) >= 4;
    }

    public function cleanRow(array $row): array
    {
        $cleaned = [];
        $fieldMappings = [
            'nombres' => ['nombres', 'first_name', 'firstname', 'primer_nombre'],
            'apellidos' => ['apellidos', 'last_name', 'lastname', 'segundo_nombre'],
            'tipo_documento' => ['tipo_documento', 'document_type', 'tipo', 'type'],
            'numero_documento' => ['numero_documento', 'document_number', 'numero', 'number', 'identificacion', 'identification'],
            'edad' => ['edad', 'age'],
            'fecha_nacimiento' => ['fecha_nacimiento', 'birth_date', 'nacimiento'],
            'genero' => ['genero', 'gender', 'sexo'],
            'correo' => ['correo', 'email'],
            'telefono' => ['telefono', 'phone', 'celular'],
            'direccion' => ['direccion', 'address'],
            'sector' => ['sector'],
            'barrio' => ['barrio', 'neighborhood'],
            'comuna' => ['comuna', 'commune'],
            'condicion' => ['condicion', 'condition'],
            'etnia' => ['etnia', 'ethnicity'],
            'nivel_estudio' => ['nivel_estudio', 'education'],
            'dignatario' => ['dignatario', 'is_public', 'public_figure'],
        ];

        foreach ($fieldMappings as $target => $sources) {
            foreach ($sources as $source) {
                if (isset($row[$source]) && $row[$source] !== '') {
                    $cleaned[$target] = trim((string) $row[$source]);
                    break;
                }
            }
        }

        if (! isset($cleaned['nombres']) && ! isset($cleaned['apellidos'])) {
            if (isset($row['nombre_completo']) || isset($row['fullname'])) {
                $nombreCompleto = $row['nombre_completo'] ?? $row['fullname'] ?? '';
                $parts = explode(' ', trim($nombreCompleto));
                $cleaned['nombres'] = array_shift($parts) ?? '';
                $cleaned['apellidos'] = implode(' ', $parts);
            }
        }

        if (isset($cleaned['tipo_documento'])) {
            $cleaned['tipo_documento'] = $this->getTipoDocumentoDataCenter($cleaned['tipo_documento']);
        }

        if (isset($cleaned['genero'])) {
            $cleaned['genero'] = $this->getGeneroDataCenter($cleaned['genero']);
        }

        if (isset($cleaned['etnia'])) {
            $cleaned['etnia'] = $this->getNombreEtniaDataCenter($cleaned['etnia']);
        }

        if (isset($cleaned['nivel_estudio'])) {
            $cleaned['nivel_estudio'] = $this->getNombreNivelEstudioDataCenter($cleaned['nivel_estudio']);
        }

        if (isset($cleaned['condicion'])) {
            $cleaned['condicion'] = $this->getNombreCondicionDataCenter($cleaned['condicion']);
        }

        if (isset($cleaned['edad']) && is_numeric($cleaned['edad'])) {
            $cleaned['edad'] = (int) $cleaned['edad'];
            if (! isset($cleaned['fecha_nacimiento'])) {
                $cleaned['fecha_nacimiento'] = $this->getDateDataCenter($cleaned['edad']);
            }
        }

        if (isset($cleaned['dignatario'])) {
            $cleaned['dignatario'] = in_array(strtolower($cleaned['dignatario']), ['1', 'true', 'yes', 'si', 'sí'], true);
        }

        return $cleaned;
    }
}
