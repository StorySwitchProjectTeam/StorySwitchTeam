<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$directory = 'json/';
$jsonList = [];

if (is_dir($directory)) {
    $files = scandir($directory);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($ext === 'json') {
                $jsonList[] = $file;
            }
        }
    }
}

echo json_encode(array_values($jsonList));
?>