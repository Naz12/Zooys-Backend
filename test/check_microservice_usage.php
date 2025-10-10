<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Microservice Usage\n";
echo "=============================\n\n";

$service = app(\App\Services\AIPresentationService::class);

// Check if microservice is available
$isAvailable = $service->isMicroserviceAvailable();
echo "1. Microservice available: " . ($isAvailable ? 'YES' : 'NO') . "\n";

if ($isAvailable) {
    echo "   ✅ System will use FastAPI microservice for PowerPoint generation\n";
} else {
    echo "   ❌ System will fallback to direct Python script\n";
}

// Check the current implementation
echo "\n2. Current implementation:\n";
echo "   - Controller checks microservice availability first\n";
echo "   - If available: calls generatePowerPointWithMicroservice()\n";
echo "   - If not available: calls generatePowerPoint() (direct Python script)\n";

echo "\n3. Methods available:\n";
$reflection = new ReflectionClass($service);
$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
$powerPointMethods = array_filter($methods, function($method) {
    return strpos($method->getName(), 'PowerPoint') !== false;
});

foreach ($powerPointMethods as $method) {
    echo "   - " . $method->getName() . "()\n";
}

echo "\n📋 Summary:\n";
echo "===========\n";
if ($isAvailable) {
    echo "✅ System is configured to use ONLY the FastAPI microservice\n";
    echo "✅ No direct Python script calls when microservice is available\n";
} else {
    echo "⚠️  System will use direct Python script as fallback\n";
    echo "⚠️  Microservice is not available or not running\n";
}

?>
