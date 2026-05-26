<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApiClient;
use Illuminate\Database\Seeder;

class ApiClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Vive Digital',
                'slug' => 'vive-digital',
                'description' => 'Plataforma de servicios digitales de la Alcaldía',
                'is_active' => true,
                'is_trusted' => true,
                'rate_limit_per_minute' => 200,
            ],
            [
                'name' => 'Sistema de Votaciones',
                'slug' => 'votaciones',
                'description' => 'Módulo de participación ciudadana y consultas',
                'is_active' => true,
                'is_trusted' => true,
                'rate_limit_per_minute' => 200,
            ],
            [
                'name' => 'Registro canino',
                'slug' => 'registro-canino',
                'description' => 'Registro caninos de manejo especial',
                'is_active' => true,
                'is_trusted' => true,
                'rate_limit_per_minute' => 200,
            ],
        ];

        foreach ($clients as $client) {
            ApiClient::firstOrCreate(
                ['slug' => $client['slug']],
                $client
            );
        }

        $this->command->info('API Clients seeded successfully.');
    }
}
