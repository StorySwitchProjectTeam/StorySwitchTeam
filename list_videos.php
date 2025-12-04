<?php
// Permet à ton JS de lire la réponse sans blocage
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Le dossier à scanner
$directory = 'videos/';
$videoList = [];

// Vérifie si le dossier existe
if (is_dir($directory)) {
    // Scanne tous les fichiers du dossier
    $files = scandir($directory);
    
    // Extensions autorisées
    $allowed_extensions = ['mp4', 'webm', 'ogg', 'mov'];

    foreach ($files as $file) {
        // On ignore les dossiers . et ..
        if ($file !== '.' && $file !== '..') {
            // Récupère l'extension
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            // Si c'est une vidéo valide
            if (in_array($ext, $allowed_extensions)) {
                // Ajoute le chemin complet (ex: "videos/intro.mp4")
                $videoList[] = $directory . $file;
            }
        }
    }
}

// Renvoie le tableau en JSON pur
echo json_encode(array_values($videoList));
?>