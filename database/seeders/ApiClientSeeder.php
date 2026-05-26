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
                'rate_limit_per_minute' => 120,
            ],
            [
                'name' => 'Sistema de Votaciones',
                'slug' => 'votaciones',
                'description' => 'Módulo de participación ciudadana y consultas',
                'is_active' => true,
                'is_trusted' => false,
                'rate_limit_per_minute' => 80,
            ],
            [
                'name' => 'Salubridad',
                'slug' => 'salubridad',
                'description' => 'Sistema de salud pública municipal',
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
