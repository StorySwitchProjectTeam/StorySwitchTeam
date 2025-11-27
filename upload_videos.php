<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$targetDir = "videos/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$response = ["success" => 0, "failed" => 0, "message" => ""];

if (!empty($_FILES['videos'])) {
    $count = count($_FILES['videos']['name']);
    
    for ($i = 0; $i < $count; $i++) {
        $fileName = basename($_FILES['videos']['name'][$i]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        
        $allowTypes = ['mp4', 'webm', 'ogg', 'mov'];
        
        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES['videos']['tmp_name'][$i], $targetFilePath)) {
                $response['success']++;
            } else {
                $response['failed']++;
            }
        } else {
            $response['failed']++;
        }
    }
    $response['message'] = "Upload terminé : " . $response['success'] . " réussis, " . $response['failed'] . " échoués.";
} else {
    $response['message'] = "Aucun fichier reçu.";
}

echo json_encode($response);
?>