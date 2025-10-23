<?php
/**
 * Test token parsing and authentication
 */

require_once 'vendor/autoload.php';

// Test token parsing
$token = '1|VSwn9FCqLFivSUGJKMukhq7kcrnXDK8h6JQmleJX97994aca';
$parts = explode('|', $token);

echo "Token: {$token}\n";
echo "ID: {$parts[0]}\n";
echo "Token part: {$parts[1]}\n";

// Test if token exists in database
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tokenExists = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->exists();
echo "Token exists in DB: " . ($tokenExists ? 'Yes' : 'No') . "\n";

if ($tokenExists) {
    $tokenRecord = Laravel\Sanctum\PersonalAccessToken::where('token', hash('sha256', $parts[1]))->first();
    echo "Token ID: {$tokenRecord->id}\n";
    echo "Token name: {$tokenRecord->name}\n";
    echo "Token abilities: " . json_encode($tokenRecord->abilities) . "\n";
    echo "Token expires at: {$tokenRecord->expires_at}\n";
    echo "Token created at: {$tokenRecord->created_at}\n";
}





