<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Convierte la columna 'source_project' de string a JSON (array de proyectos origen).
 *
 * Es idempotente y segura para entornos que ya tenían la tabla creada con la
 * columna como string(100): amplía la columna, envuelve cada valor existente en
 * un array JSON (["valor"]) y luego cambia el tipo a JSON. En instalaciones
 * nuevas (migrate:fresh) la columna ya se crea como JSON, por lo que esta
 * migración no hace nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personas') || ! Schema::hasColumn('personas', 'source_project')) {
            return;
        }

        $connection = Schema::getConnection();

        // Solo MySQL/MariaDB requieren conversión de datos. En otros drivers
        // (ej: sqlite en tests) la migración de creación ya deja la columna como JSON.
        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        $column = $connection->selectOne(
            'SELECT DATA_TYPE AS data_type FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['personas', 'source_project']
        );

        // Ya es JSON (instalación nueva): nada que convertir.
        if (! $column || strtolower((string) $column->data_type) === 'json') {
            return;
        }

        // 1) Ampliar la columna para poder almacenar el JSON envuelto sin truncar.
        $connection->statement('ALTER TABLE `personas` MODIFY `source_project` VARCHAR(255) NULL');

        // 2) Convertir cada valor string existente a un array JSON; vacíos -> NULL.
        $connection->statement(
            "UPDATE `personas`
             SET `source_project` = CASE
                 WHEN `source_project` IS NULL OR TRIM(`source_project`) = '' THEN NULL
                 ELSE JSON_ARRAY(TRIM(`source_project`))
             END"
        );

        // 3) Cambiar el tipo definitivo a JSON.
        $connection->statement('ALTER TABLE `personas` MODIFY `source_project` JSON NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('personas') || ! Schema::hasColumn('personas', 'source_project')) {
            return;
        }

        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        $column = $connection->selectOne(
            'SELECT DATA_TYPE AS data_type FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['personas', 'source_project']
        );

        if (! $column || strtolower((string) $column->data_type) !== 'json') {
            return;
        }

        // Ampliar como texto para poder volcar el primer elemento del array.
        $connection->statement('ALTER TABLE `personas` MODIFY `source_project` VARCHAR(255) NULL');

        // Tomar el primer elemento del array JSON como valor string.
        $connection->statement(
            "UPDATE `personas`
             SET `source_project` = CASE
                 WHEN `source_project` IS NULL THEN NULL
                 ELSE JSON_UNQUOTE(JSON_EXTRACT(`source_project`, '$[0]'))
             END"
        );

        // Restaurar el tipo original string(100).
        $connection->statement('ALTER TABLE `personas` MODIFY `source_project` VARCHAR(100) NULL');
    }
};
