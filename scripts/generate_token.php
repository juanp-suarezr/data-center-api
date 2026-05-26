<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap the application kernel to be able to use Eloquent
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApiClient;

$slug = $argv[1] ?? 'vive-digital';
$name = $argv[2] ?? 'cli-token';

$client = ApiClient::where('slug', $slug)->first();

if (! $client) {
    echo "ERROR: client not found for slug={$slug}\n";
    exit(1);
}

$token = $client->createToken($name, ['person:read','person:write','person:sync'])->plainTextToken;

echo "CLIENT_ID: {$client->id}\n";
echo "TOKEN: {$token}\n";
