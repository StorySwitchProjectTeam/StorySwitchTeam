<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$input = json_decode(file_get_contents("php://input"), true);
$filename = basename($input['filename'] ?? '');
$targetFile = "videos/" . $filename;

$response = ["success" => false, "message" => ""];

if (!$filename) {
    $response['message'] = "Nom de fichier manquant.";
} elseif (file_exists($targetFile)) {
    if (unlink($targetFile)) {
        $response['success'] = true;
        $response['message'] = "Vidéo supprimée.";
    } else {
        $response['message'] = "Erreur lors de la suppression.";
    }
} else {
    $response['message'] = "Fichier introuvable.";
}

echo json_encode($response);
?>