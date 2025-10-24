<?php

require_once 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$file = \App\Models\FileUpload::find(114);
if($file) {
    echo "File ID: " . $file->id . "\n";
    echo "File path: " . $file->file_path . "\n";
    echo "File type: " . $file->file_type . "\n";
    echo "File exists: " . (file_exists(storage_path('app/' . $file->file_path)) ? 'Yes' : 'No') . "\n";
    echo "Full path: " . storage_path('app/' . $file->file_path) . "\n";
} else {
    echo "File not found in database\n";
}

?>

