<?php
// Autoriser les requêtes depuis n'importe quelle origine (utile en dev)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Récupérer les données envoyées
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->filename) && !empty($data->content)) {
    
    // Créer le dossier 'json' s'il n'existe pas
    if (!file_exists('json')) {
        mkdir('json', 0777, true);
    }

    // Sécuriser le nom du fichier (éviter les ../)
    $filename = basename($data->filename);
    $filePath = 'json/' . $filename;

    // Convertir le contenu en JSON joli
    $jsonContent = json_encode($data->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Écrire le fichier
    if (file_put_contents($filePath, $jsonContent)) {
        http_response_code(200);
        echo json_encode(["message" => "Fichier $filename sauvegardé avec succès."]);
    } else {
        http_response_code(503);
        echo json_encode(["message" => "Impossible d'écrire le fichier."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Données incomplètes."]);
}
?>