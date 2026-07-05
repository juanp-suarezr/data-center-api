<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentType: string
{
    case CEDULA_CIUDADANIA = 'CC';
    case TARJETA_IDENTIDAD = 'TI';
    case CEDULA_EXTRANJERIA = 'CE';
    case NIT = 'NIT';
    case PASAPORTE = 'PP';
    case REGISTRO_CIVIL = 'RC';
    case PERMISO_ESPECIAL = 'PEP';
    case PERMISO_PROTECCION = 'PPT';
    case OTRO = 'O';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::CEDULA_CIUDADANIA => 'Cédula de Ciudadanía',
            self::TARJETA_IDENTIDAD => 'Tarjeta de Identidad',
            self::CEDULA_EXTRANJERIA => 'Cédula de Extranjería',
            self::NIT => 'NIT',
            self::PASAPORTE => 'Pasaporte',
            self::REGISTRO_CIVIL => 'Registro Civil',
            self::PERMISO_ESPECIAL => 'Permiso Especial de Permanencia',
            self::PERMISO_PROTECCION => 'Permiso de Protección Temporal',
            self::OTRO => 'Otro',
        };
    }
}
