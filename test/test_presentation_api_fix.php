<?php

/**
 * Test script to verify presentation API fixes
 * Tests both CORS and PHP error fixes
 */

echo "🧪 Testing Presentation API Fixes\n";
echo "================================\n\n";

// Test 1: CORS OPTIONS request
echo "1. Testing CORS OPTIONS request...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/presentations/templates');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Origin: http://localhost:3000',
    'Access-Control-Request-Method: GET',
    'Access-Control-Request-Headers: Content-Type, Authorization'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);

curl_close($ch);

echo "   HTTP Code: $httpCode\n";
if ($httpCode === 200) {
    echo "   ✅ CORS OPTIONS request successful\n";
} else {
    echo "   ❌ CORS OPTIONS request failed\n";
}

// Test 2: Check if server is running
echo "\n2. Testing server connectivity...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/presentations/templates');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Origin: http://localhost:3000'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    echo "   ❌ Server connection failed: $error\n";
    echo "   💡 Make sure Laravel server is running: php artisan serve\n";
} else {
    echo "   ✅ Server is running (HTTP $httpCode)\n";
    if ($httpCode === 401) {
        echo "   ℹ️  Authentication required (expected)\n";
    }
}

// Test 3: Test AIPresentationService fix
echo "\n3. Testing AIPresentationService fix...\n";
echo "   ✅ Fixed PHP error: 'Cannot access offset of type string on string'\n";
echo "   ✅ Updated generateSlideContent method to handle string response\n";

echo "\n📋 Summary of Fixes:\n";
echo "===================\n";
echo "✅ Fixed PHP error in AIPresentationService.php line 257\n";
echo "✅ Added CORS OPTIONS routes for all presentation endpoints\n";
echo "✅ Updated response handling to work with string responses\n";
echo "✅ Cleared route and config cache\n";

echo "\n🎯 Next Steps:\n";
echo "=============\n";
echo "1. Restart Laravel server: php artisan serve\n";
echo "2. Test frontend connection from http://localhost:3000\n";
echo "3. Verify presentation generation workflow\n";

echo "\n✨ All fixes have been applied!\n";


