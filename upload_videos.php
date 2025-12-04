<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Active l'affichage des erreurs PHP pour le debug (à commenter en prod)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$targetDir = "videos/";
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0777, true)) {
        echo json_encode(["success" => 0, "failed" => 1, "message" => "Impossible de créer le dossier 'videos/'"]);
        exit;
    }
}

$response = ["success" => 0, "failed" => 0, "errors" => [], "message" => ""];

if (!empty($_FILES['videos'])) {
    $count = count($_FILES['videos']['name']);

    for ($i = 0; $i < $count; $i++) {
        $fileName = basename($_FILES['videos']['name'][$i]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $fileError = $_FILES['videos']['error'][$i];

        $allowTypes = ['mp4', 'webm', 'ogg', 'mov'];

        // Vérification des erreurs natives PHP
        if ($fileError !== UPLOAD_ERR_OK) {
            $response['failed']++;
            $msg = "Erreur inconnue";
            switch ($fileError) {
                case UPLOAD_ERR_INI_SIZE:
                    $msg = "Fichier trop lourd (limite php.ini)";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $msg = "Fichier trop lourd (limite form)";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $msg = "Upload partiel (interrompu)";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $msg = "Aucun fichier envoyé";
                    break;
            }
            $response['errors'][] = "$fileName : $msg";
            continue; // On passe au suivant
        }

        if (in_array($fileType, $allowTypes)) {
            if (move_uploaded_file($_FILES['videos']['tmp_name'][$i], $targetFilePath)) {
                $response['success']++;
            } else {
                $response['failed']++;
                $response['errors'][] = "$fileName : Erreur lors du déplacement du fichier (Permissions ?)";
            }
        } else {
            $response['failed']++;
            $response['errors'][] = "$fileName : Format $fileType non autorisé";
        }
    }

    // Message de résumé
    if (count($response['errors']) > 0) {
        $response['message'] = "Terminé avec erreurs : " . implode(", ", $response['errors']);
    } else {
        $response['message'] = "Upload terminé : " . $response['success'] . " vidéos ajoutées.";
    }

} else {
    $response['message'] = "Aucune donnée de fichier reçue (Vérifie post_max_size dans php.ini).";
}

echo json_encode($response);
?>